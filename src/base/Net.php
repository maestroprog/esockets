<?php
/**
 ** Net common class.
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.09.2015
 * Time: 20:42
 */

namespace Esockets\base;

use Esockets\debug\Log;
use Esockets\io\base\Provider;
use Esockets\io\TcpSocket;
use Esockets\protocol\Easy;

abstract class Net implements NetInterface
{
    const SOCKET_WAIT = 1000; // 1 ms ожидание на повторные операции на сокете

    const SOCKET_TIMEOUT = 1;

    const SOCKET_RECONNECT = 10; // 10s ожидание перед переподключением

    const LIVE_LAST_PING = 'live_last_ping';
    const LIVE_LAST_RECONNECT = 'live_last_reconnect';
    const LIVE_LAST_CHECK = 'live_last_check';

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

    /**
     * @var callable
     */
    private $event_read;

    /**
     * @var callable
     */
    private $event_pong;

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

    /**
     * @param string $addr
     * @param int $port
     * @return mixed
     */
    abstract protected function getPeerName(string &$addr, int &$port);

    /**
     * @return bool
     * возвращает true, если соединение включено, false в противном случае
     */
    abstract public function is_connected();

    /**
     * Функция должна создавать интерфейс ввода-вывода.
     *
     * @return Provider
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

    public function read(bool $need = false)
    {
        if (false === ($data = $this->IO->read($need))) {
            return false;
        } elseif (!$need && $data !== null) {
            $this->_onRead($data);
        } else {
            return $data;
        }
        return false;
    }

    public function send($data)
    {
        $this->mid++;
        return $this->IO->send($data);
    }

    abstract protected function _onDisconnect();

    public function onRead(callable $callback)
    {
        $this->event_read = $callback;
    }

    protected function _onRead(&$data)
    {
        if (is_object($data) && $data instanceof Ping) {
            if (is_callable($this->event_pong)) {
                call_user_func($this->event_pong, $data);
            }
        } elseif (is_callable($this->event_read)) {
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

    public function ping()
    {
        if ($this->event_pong) return;

        $ping = new Ping(rand(1000, 9999), false);
        $this->event_pong = function (Ping $msg) use ($ping) {
            if ($msg->getValue() !== $ping->getValue()) {
                throw new \Exception('Incorrect ping data');
            } else {
                $this->setTime(self::LIVE_LAST_PING);
            }
            $this->event_pong;
        };
        $this->send($ping);
        unset($ping);
    }

    /**
     * @return bool
     */
    public function live()
    {
        $this->read();
        if ($this->is_connected()) {
            $this->setTime();
            if (($this->getTime(self::LIVE_LAST_PING) + self::SOCKET_TIMEOUT * 2) <= time()) {
                // иногда пингуем соединение
                $this->ping();
            }
        } elseif ($this->socket_reconnect && $this->getTime() + self::SOCKET_TIMEOUT > time()) {
            if ($this->getTime(self::LIVE_LAST_RECONNECT) + self::SOCKET_RECONNECT <= time()) {
                if ($this->connect()) {
                    $this->setTime();
                }
            } else {
                $this->setTime(self::LIVE_LAST_RECONNECT);
            }
        } else {
            return false;
        }
        return true;
    }

    protected function getTime(string $key = self::LIVE_LAST_CHECK): int
    {
        return (int)$this->get($key);
    }

    protected function setTime(string $key = self::LIVE_LAST_CHECK)
    {
        $this->set($key, time());
    }
}
