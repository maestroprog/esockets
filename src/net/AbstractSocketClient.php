<?php

namespace Esockets\net;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\BlockingInterface;
use Esockets\base\PingPacket;

class AbstractSocketClient extends AbstractClient implements BlockingInterface
{
    /**
     * @var int type of socket
     */
    protected $socketDomain;
    protected $socket;

    protected $serverAddress;
    protected $clientAddress;


    /** Интервал времени ожидания между попытками при чтении/записи. */
    const SOCKET_WAIT = 1;

    /** Константы внутренних ошибок. */
    const ERROR_NOTHING = 0;    // нет ошибки
    const ERROR_AGAIN = 1;      // ошибка, просьба повторить операцию
    const ERROR_SKIP = 2;       // ошибка, просьба пропустить операцию
    const ERROR_FATAL = 4;      // фатальная ошибка
    const ERROR_UNKNOWN = 8;    // неизвестная необрабатываемая ошибка

    /** Константы операций ввода/вывода. */
    const OP_READ = 0;
    const OP_WRITE = 1;

    /**
     * @var array Известные и обрабатываемые ошибки сокетов
     */
    protected static $catchableErrors = [];

    /** Список известных ошибок для настройки обработчика */
    const ERRORS_KNOW = [
        'SOCKET_EWOULDBLOCK' => self::ERROR_NOTHING,
        'SOCKET_EAGAIN' => self::ERROR_AGAIN,
        'SOCKET_TRY_AGAIN' => self::ERROR_AGAIN,
        'SOCKET_EPIPE' => self::ERROR_FATAL,
        'SOCKET_ENOTCONN' => self::ERROR_FATAL,
        'SOCKET_ECONNABORTED' => self::ERROR_FATAL,
        'SOCKET_ECONNRESET' => self::ERROR_FATAL,
    ];

    private $eventDisconnect;
    private $eventRead;
    private $eventPong;

    public function __construct()
    {
        $this->checkConstants();
    }

    protected function isUnixAddress(): bool
    {
        return $this->socketDomain === AF_UNIX;
    }

    protected function isIpAddress(): bool
    {
        return $this->socketDomain === AF_INET || $this->socketDomain === AF_INET6;
    }

    /**
     * @inheritdoc
     */
    public function getServerAddress(): AbstractAddress
    {
        return $this->serverAddress;
    }

    /**
     * Вернет адрес клиента, который подключен к серверу.
     *
     * @return AbstractAddress
     */
    public function getClientAddress(): AbstractAddress
    {
        if (is_null($this->clientAddress) || !($this->clientAddress instanceof AbstractAddress)) {
            $addr = $port = null;
            socket_getsockname($this->socket, $addr, $port);
            if ($this->socketDomain === AF_UNIX) {
                $this->clientAddress = new Ipv4Address($addr, $port);
            } else {
                $this->clientAddress = new UnixAddress($addr);
            }
        }
        return $this->clientAddress;
    }

    public function reconnect(): bool
    {
        $this->disconnect();
        try {
            $this->connect($this->serverAddress);
        } catch ($e) {
            return false;
        }
        return true;
    }

    /**
     * Поддерживает жизнь соединения.
     * Что делает:
     * - контролирует текущее состояние соединения,
     * - проверяет связь с заданным интервалом,
     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи, если это включено,
     *
     * Возвращает true, если сокет жив, false если не работает.
     * Можно использовать в бесконечном цикле:
     * while ($NET->live()) {
     *     // тут делаем что-то.
     * }
     *
     * @return bool
     */
    public function live()
    {
        // TODO: Implement live() method.
    }

    /**
     * @param AbstractAddress $address
     * @return void
     */
    public function connect(AbstractAddress $address)
    {
        // TODO: Implement connect() method.
    }

    public function onConnect(callable $callback)
    {
        // TODO: Implement onConnect() method.
    }

    public function isConnected(): bool
    {
        // TODO: Implement isConnected() method.
    }

    public function disconnect()
    {
        if ($this->socket) {
            $this->block(); // блокируем сокет перед завершением его работы
            socket_shutdown($this->socket);
            socket_close($this->socket);
            $this->callDisconnectEvent();
        } else {
            throw new \LogicException('Socket already is closed.');
        }
    }

    public function onDisconnect(callable $callback)
    {
        $this->eventDisconnect = $callback;
    }

    protected function callDisconnectEvent()
    {
        if (is_callable($this->eventDisconnect)) {
            call_user_func($this->eventDisconnect);
        }
    }

    public function ping()
    {
        $ping = PingPacket::request(rand(1000, 9999));
        $this->eventPong = function (PingPacket $msg) use ($ping) {
            if ($msg->getValue() !== $ping->getValue()) {
                throw new \Exception('Incorrect ping data');
            } else {
                $this->setTime(self::LIVE_LAST_PING);
            }
            $this->eventPong;
        };
        $this->send($ping);
        unset($ping);
    }

    public function pong(PingPacket $pingData)
    {
        // TODO: Implement pong() method.
    }

    public function block()
    {
        socket_set_block($this->socket);
    }

    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }


    /**
     * Функция проверяет, установлены ли некоторые константы обрабатываемых ошибок сокетов.
     */
    protected function checkConstants()
    {
        if (!empty(self::$catchableErrors)) return;
        foreach (self::ERRORS_KNOW as $const => $selfType) {
            if (defined($const)) {
                self::$catchableErrors[constant($const)] = $selfType;
            }
        }
    }

    /**
     * Функция возвращает одну из констант self::ERROR_*
     * Параметр $errno - номер ошибки функции socket_last_error()
     * Параметр $operation - номер операции; 1 = запись, 0 = чтение.
     *
     * @param int $errno
     * @param int $operation
     * @return int
     */
    protected function getErrorType(int $errno, int $operation): int
    {
        if ($errno === 0) {
            return self::ERROR_NOTHING;
        } elseif (isset(self::$catchableErrors[$errno])) {
            if (
                self::$catchableErrors[$errno] !== self::ERROR_NOTHING
                && self::$catchableErrors[$errno] !== self::ERROR_AGAIN // for unix-like systems
            ) {
                Log::log(sprintf(
                    'Socket catch error %s at %s: %d',
                    socket_strerror($errno),
                    $operation ? 'WRITING' : 'READING',
                    $errno
                ));
            }
            return self::$catchableErrors[$errno];
        } else {
            Log::log(sprintf('Unknown socket error %d: %s', $errno, socket_strerror($errno)));
            return self::ERROR_UNKNOWN;
        }
    }
}
