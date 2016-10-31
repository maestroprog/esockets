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

use maestroprog\esockets\debug\Log;
use maestroprog\esockets\io\base\Aware;
use maestroprog\esockets\io\base\Provider;
use maestroprog\esockets\io\TcpSocket;
use maestroprog\esockets\protocol\Easy;

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

    /**
     * @var Provider
     */
    protected $IO;


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
    public function connect()
    {
        $this->createIO();
        $addr = null;
        $port = 0;
        socket_getsockname($this->connection, $addr, $port);
        $this->set('my_ip', $addr);
        $this->set('my_port', $port);
        $this->getPeerName($addr, $port);
        $this->set('peer_ip', $addr);
        $this->set('peer_port', $port);
        return true;
    }

    abstract protected function getPeerName(string &$addr, int &$port);

    /**
     * @return bool
     * возвращает true, если соединение включено, false в противном случае
     */
    abstract public function is_connected();

    /**
     * Функция должна создавать интерфейс ввода-вывода.
     *
     * @return Aware
     */
    final public function createIO()
    {
        $this->IO or $this->IO = new Provider(Easy::class, new TcpSocket($this));
        return $this->IO;
    }

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

    public function read($need = false)
    {
        try {
            if (false === ($data = $this->IO->read($need))) {
                return false;
            } elseif (!$need && $data !== null) {
                $this->_onRead($data);
            } else {
                return $data;
            }
        } catch (\Throwable $e) {
            // ????? todo
            throw $e;
        }
        return false;
    }

    public function send($data)
    {
        $this->mid++;
        try {
            return $this->IO->send($data);
        } catch (\Throwable $e) {
            // ????? todo
            throw $e;
        }
        return false;
    }

    public function ping()
    {
        // todo
        /*$data = rand(1000, 9999);
        $this->event_pong = function ($msg) use ($data) {
            if ($msg === $data) {
                \maestroprog\esockets\debug\Log::log('ping corrected!');
            } else {
                \maestroprog\esockets\debug\Log::log('PING FAIL!');
            }
        };
        $this->_send($data, self::DATA_INT | self::DATA_PING_PONG); // todo
        \maestroprog\esockets\debug\Log::log('ping sended');*/
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

    public function setBlock()
    {
        socket_set_block($this->connection);
    }

    public function setNonBlock()
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

    public function getConnection()
    {
        return $this->connection;
    }

    public function getPeerAddress(): array
    {
        if ($this->socket_domain === AF_INET) {
            return [
                $this->get('peer_ip'),
                $this->get('peer_port')
            ];
        } else {
            return [
                $this->get('peer_address'), 0
            ];
        }
    }

    public function getMyAddress(): array
    {
        if ($this->socket_domain === AF_INET) {
            return [
                $this->get('my_ip'),
                $this->get('my_port')
            ];
        } else {
            return [
                $this->get('my_address'), 0
            ];
        }
    }

    public function getAddress(): string
    {
        return implode(':', $this->getPeerAddress());
    }
}
