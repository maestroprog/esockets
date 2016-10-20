<?php
/**
 ** Net common class.
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:42
 */

namespace maestroprog\esockets\base;

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

    //abstract public function createIO();

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

    }

    public function send($data)
    {
        $this->mid++;

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
        $this->_send($data, self::DATA_INT | self::DATA_PING_PONG); // todo
        error_log('ping sended');
    }

    /**
     * @todo допилить
     * @return bool
     */
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
        $this->set($key, time());
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
     * @todo
     * @deprecated
     * @param $length
     * @param bool $required
     * @return bool|string
     * функция, отвечающая за чтения входящих пакетов данных
     */
    private function _read($length, $required = false)
    {
    }

    /**
     * @todo
     * @deprecated
     * @param $data
     * @param int $flag
     * @return bool
     * @throws \Exception
     * функция, отвечающая за отправку пакетов данных
     */
    private function _send($data, $flag = 0)
    {
    }

    public function getConnection(): resource
    {
        return $this->connection;
    }
}
