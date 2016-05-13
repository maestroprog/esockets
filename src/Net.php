<?php
/**
 ** Net code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:42
 */

namespace Esockets;


function error_log($msg)
{
    echo $msg . PHP_EOL;
    \error_log($msg);
}


abstract class Net implements NetInterface
{
    const SOCKET_WAIT = 1000; // 1 ms ожидание на повторные операции на сокете

    const SOCKET_TIMEOUT = 30;

    const SOCKET_RECONNECT = 10; // 30s ожидание перед переподключением

    /**
     * @var int type of socket
     */
    protected $socket_domain = AF_INET;

    /**
     * @var string address of socket connection
     */
    protected $socket_address = '127.0.0.1';

    /**
     * @var int port of socket connection
     */
    protected $socket_port = 8082;

    /**
     * @var bool
     * automatic reconnect socket for connection broken
     */
    protected $socket_reconnect = false;

    /**
     * @var resource of socket connection
     */
    protected $connection;

    /**
     * @var array user-defined variables and flags of the connection
     */
    protected $vars = [];


    /* event variables */

    /**
     * @var callable
     */
    private $event_read;

    /**
     * @var callable
     */
    private $event_pong;

    /* private variables */

    /**
     * @var int
     * auto increment message id
     * #for send
     */
    private $mid = 0;

    public function __construct($config = [])
    {
        foreach ($config as $key => $val)
            if (isset($this->{$key})) $this->{$key} = $val;
    }

    /**
     * @param $name
     * @return bool
     * get user variable
     */
    public function get($name)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : null;
    }

    /**
     * @param $name
     * @param $value
     * set user variable
     */
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * @return bool
     */
    abstract public function connect();

    /**
     * @return bool
     * возвращает true, если соединение включено, false в противном случае
     */
    abstract public function is_connected();

    public function disconnect()
    {
        if ($this->connection) {
            $this->setBlock(); // блокируем сокет перед завершением его работы
            socket_shutdown($this->connection);
            socket_close($this->connection);
            $this->_onDisconnect();
        } else {
            trigger_error('Socket already closed');
        }
    }

    public function read()
    {
        // read message meta
        if (($data = $this->_read(5)) !== false) {
            list($length, $flag) = array_values(unpack('Nvalue0/Cvalue1', $data));
            error_log('read length ' . $length);
            error_log('flag ' . $flag);
            error_log('read try ' . $length . ' bytes');
            if (($data = $this->_read($length, true)) !== false) {
                error_log('data retrieved');
            } else {
                error_log('cannot retrieve data');
            }
            if ($flag & self::DATA_JSON) {
                $data = json_decode($data, $flag & self::DATA_ARRAY ? true : false);
            } elseif ($flag & self::DATA_INT) {
                $data = (int)$data;
            } elseif ($flag & self::DATA_FLOAT) {
                $data = (float)$data;
            } else {

            }
            if ($flag & self::DATA_CONTROL) {
                // control message parser
                // @TODO
                if ($flag & self::DATA_PING_PONG) {
                    if (is_callable($this->event_pong)) {
                        call_user_func($this->event_pong, $data);
                    } else {
                        error_log('pong received');
                    }
                }
            } elseif ($flag & self::DATA_PING_PONG) {
                // отправляем исходные данные "pong" с исходным форматом, дополнительно устанавливая флаг DATA_CONTROL
                $this->_send($data, $flag | self::DATA_CONTROL);
                error_log('ping received and pong sended');
                return;
            }
            $this->_onRead($data);
        }
    }

    public function send($data)
    {
        $this->mid++;
        $flag = 0;
        switch (gettype($data)) {
            case 'boolean':
                trigger_error('Boolean data type cannot be transmitted', E_USER_WARNING);
                return false;
                break;
            case 'integer':
                $flag = self::DATA_INT;
                break;
            case 'double':
                $flag = self::DATA_FLOAT;
                break;
            case 'array':
                $flag = self::DATA_ARRAY | self::DATA_JSON;
                break;
            case 'object':
                $flag = self::DATA_EXTENDED | self::DATA_JSON;
                trigger_error('Values of type Object cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            case 'resource':
                trigger_error('Values of type Resource cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            case 'NULL':
                trigger_error('Null data type cannot be transmitted', E_USER_WARNING);
                return false;
                break;
            case 'unknown type':
                trigger_error('Values of Unknown type cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            default:
                $flag |= self::DATA_STRING;
        }
        if ($flag & self::DATA_JSON)
            $data = json_encode($data);
        $length = strlen($data);
        if ($length >= 0xffffffff) { // 4294967296 bytes
            trigger_error('Big data size to send! I can split it\'s', E_USER_ERROR); // кто-то попытался передать более 4 ГБ за раз, выдаем ошибку
            // СТОП СТОП СТОП! Какой идиот за раз будет передавать 4 ГБ?
            //...
            return false;
        } else {
            return $this->_send($data, $flag);
        }
    }

    public function ping()
    {
        $data = rand(1000, 9999);
        $this->event_pong = function ($msg) use ($data) {
            if ($msg === $data) {
                error_log('ping corrected!');
            } else {
                error_log('PING FAIL!');
            }
        };
        $this->_send($data, self::DATA_INT | self::DATA_PING_PONG);
        error_log('ping sended');
    }

    public function live()
    {
        $this->read();
        if ($this->is_connected()) {
            $this->live_checked();
            if (($this->get('live_last_ping') + self::SOCKET_TIMEOUT * 2) <= time())
                $this->ping() && $this->live_checked('live_last_ping'); // иногда пингуем соединение
        } elseif ($this->socket_reconnect && $this->get('live_last_check') + self::SOCKET_TIMEOUT > time()) {
            if ($this->get('live_last_reconnect') + self::SOCKET_RECONNECT <= time()) {
                if ($this->connect())
                    $this->live_checked();
            } else {
                $this->live_checked('live_last_reconnect');
            }
        } else {
            return false;
        }
        return true;
    }

    private function live_checked($key = 'live_last_check')
    {
        $this->set('live_last_check', time());
    }

    abstract protected function _onDisconnect();

    public function onRead(callable $callback)
    {
        $this->event_read = $callback;
    }

    protected function _onRead(&$data)
    {
        if (is_callable($this->event_read)) {
            call_user_func_array($this->event_read, [$data]);
            return true;
        } else {
            return false;
        }
    }

    protected function setBlock()
    {
        socket_set_block($this->connection);
    }

    protected function setNonBlock()
    {
        socket_set_nonblock($this->connection);
    }

    /**
     * @param $length
     * @param bool $required
     * @return bool|string
     * функция, отвечающая за чтения входящих пакетов данных
     */
    private function _read($length, $required = false)
    {
        $buffer = '';
        $try = 0;
        while ($length > 0) {
            $data = socket_read($this->connection, $length);
            error_log('data is ' . var_export($data, true) . ' from ' . get_class($this));
            if ($data === false || $data === '') {
                switch (socket_last_error($this->connection)) {
                    case SOCKET_EAGAIN:
                        if (!strlen($buffer) && (!$required || $try++ > 100)) {
                            return false;
                        } else {
                            error_log('Socket read error: SOCKET_EAGAIN at READING');
                            usleep(self::SOCKET_WAIT);
                        }
                        break;
                    case SOCKET_EPIPE:
                    case SOCKET_ENOTCONN:
                        $this->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        return false;
                    default:
                        error_log('SOCKET READ ERROR!!!' . socket_last_error($this->connection) . ':' . socket_strerror(socket_last_error($this->connection)));
                        return false;
                    //throw new \Exception('Socket read error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
                }
                /*} elseif ($data === '') {
                    /**
                     * В документации PHP написано, что socket_read выдает false, если сокет отсоединен.
                     * Однако, как выяснилось, это не так. Если сокет отсоединен,
                     * то socket_read возвращает пустую строку. Поэтому в данном блоке будем
                     * обрабатывать ситуацию обрыва связи.
                     * TODO запилить, что описал
                     *
                    trigger_error('Socket read 0 bytes', E_USER_WARNING);
                    error_log('Пробуем получить код ошибки...');
                    //throw new \Exception('Socket read error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
                    if ($required || $try++ > 100) {
                        trigger_error('Fail require read data', E_USER_ERROR);
                    }
                    continue; // продолжаем читать в цикле*/
            } else {
                $buffer .= $data;
                $length -= strlen($data);
                $try = 0; // обнуляем счетчик попыток чтения
                usleep(self::SOCKET_WAIT);
            }
        }
        return $buffer;
    }

    /**
     * @param $data
     * @param int $flag
     * @return bool
     * @throws \Exception
     * функция, отвечающая за отправку пакетов данных
     */
    private function _send($data, $flag = 0)
    {
        $length = strlen($data);
        $data = pack('NCa*', $length, $flag, $data);
        $length += 5;
        $written = 0;
        do {
            $wrote = socket_write($this->connection, $data);
            if ($wrote === false) {
                /**
                 * @TODO как и при чтении, необходимо протестировать работу socket_write
                 * Промоделировать ситуацию, когда удаленный сокет отключился, и выяснить, что выдает socker_write
                 * и как правильно определить отключение удаленного сокета в данной функции.
                 */
                switch (socket_last_error($this->connection)) {
                    case SOCKET_EAGAIN:
                        error_log('Socket write error: SOCKET_EAGAIN at writing');
                        usleep(self::SOCKET_WAIT);
                        return false;
                        break;
                    case SOCKET_EPIPE:
                    case SOCKET_ENOTCONN:
                        $this->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        break;
                    default:
                        error_log('SOCKET WRITE ERROR!!!' . socket_last_error($this->connection));
                        throw new \Exception('Socket write error: ' . socket_strerror(socket_last_error($this->connection)), socket_last_error($this->connection));
                }
                return false;
            } elseif ($wrote === 0) {
                trigger_error('Socket written 0 bytes', E_USER_WARNING);
            } else {
                $data = substr($data, $wrote);
                $written += $wrote;
            }
        } while ($written < $length);
        return true;
    }
}