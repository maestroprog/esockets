<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 20:38
 */

namespace maestroprog\esockets\io;

use maestroprog\esockets\base\Net;
use maestroprog\esockets\debug\Log;
use maestroprog\esockets\io\base\Middleware;

class TcpSocket extends Middleware
{
    /** Интервал времени ожидания между попытками при чтении/записи. */
    const SOCKET_WAIT = 1;

    /** Константы внутренних ошибок. */
    const ERROR_NOTHING = 0;    // нет ошибки
    const ERROR_AGAIN = 1;      // ошибка, просьба повторить операцию
    const ERROR_SKIP = 2;       // ошибка, просьба пропустить операцию
    const ERROR_FATAL = 4;      // фатальная ошибка
    const ERROR_UNKNOWN = 8;    // неизвестная необработываемая ошибка

    /** Константы операций ввода/вывода. */
    const OP_READ = 0;
    const OP_WRITE = 1;

    /**
     * @var array Известные и обрабатываемые ошибки сокетов
     */
    private static $catchableErrors = [];

    /** Список известных ошибок для настройки обработчика */
    const ERRORS_KNOW = [
        'SOCKET_EWOULDBLOCK' => self::ERROR_NOTHING,
        'SOCKET_EAGAIN' => self::ERROR_AGAIN,
        'SOCKET_TRY_AGAIN' => self::ERROR_AGAIN,
        'SOCKET_EPIPE' => self::ERROR_FATAL,
        'SOCKET_ENOTCONN' => self::ERROR_FATAL,
    ];

    /**
     * @var Net
     */
    private $connection;

    /**
     * @var resource of socket
     */
    private $socket;

    public function __construct(Net $connection)
    {
        //SOCKET_EAGAIN;
        //SOCKET_TRY_AGAIN;
        $this->connection = $connection;
        $this->socket = $connection->getConnection();

        $this->checkConstants();
    }

    // todo есть некоторые непонятные моменты в приеме/отправке данных. надо потестить!
    public function read(int $length, bool $need = false)
    {
        $buffer = '';
        $try = 0;
        do {
            $data = socket_read($this->socket, $length);
            if ($data === false) return false;
            Log::log('data is ' . var_export($data, true) . ' from ' . get_class($this));
            switch ($this->errorType(socket_last_error($this->socket), self::OP_READ)) {
                case self::ERROR_NOTHING:
                    // todo
                    if ($data === false) {
                        Log::log('READ FALSE!!!!');
                        return false;
                    } elseif ($data === '') {
                        Log::log('EMPTY!!!!');
                        $this->connection->disconnect();
                        return false;
                    } else {
                        $buffer .= $data;
                        $length -= strlen($data);
                        $try = 0; // обнуляем счетчик попыток чтения
                        usleep(self::SOCKET_WAIT);
                    }
                    break;
                case self::ERROR_AGAIN:
                    var_dump($data);
                    if ($data === '' && !strlen($buffer) && (!$need || $try++ > 100)) {
                        $this->connection->disconnect(); // TODO тут тоже закрыто. выяснить почему???
                        return false;
                    } else {
                        Log::log('Socket read error: SOCKET_EAGAIN at READING');
                        usleep(self::SOCKET_WAIT);
                    }
                    continue 2;
                    break;
                case self::ERROR_SKIP:
                    return false;

                case self::ERROR_FATAL:
                    $this->connection->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                    return false;

                case self::ERROR_UNKNOWN:
                    throw new \Exception(
                        'Socket read error: '
                        . socket_strerror(socket_last_error($this->socket)),
                        socket_last_error($this->socket)
                    );
                    break;
            }
        } while ($need && $length > 0);
        return $buffer;
    }

    // todo есть некоторые непонятные моменты в приеме/отправке данных. надо потестить!
    public function send(string &$data)
    {
        $length = strlen($data);
        $written = 0;
        do {
            $wrote = socket_write($this->socket, $data);
            /**
             * @TODO как и при чтении, необходимо протестировать работу socket_write
             * Промоделировать ситуацию, когда удаленный сокет отключился, и выяснить, что выдает socker_write
             * и как правильно определить отключение удаленного сокета в данной функции.
             */
            if ($wrote === false) {

                switch ($this->errorType(socket_last_error($this->socket), self::OP_WRITE)) {
                    case self::ERROR_NOTHING:
                        var_dump($wrote);
                        throw new \Exception(
                            'Socket write no error: '
                            . socket_strerror(socket_last_error($this->socket)),
                            socket_last_error($this->socket)
                        );
                        break;
                    case self::ERROR_AGAIN:
                        usleep(self::SOCKET_WAIT);
                        continue 2;
                        break;
                    case self::ERROR_SKIP:
                        return false;

                    case self::ERROR_FATAL:
                        $this->connection->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        return false;

                    case self::ERROR_UNKNOWN:
                        throw new \Exception(
                            'Socket write error: '
                            . socket_strerror(socket_last_error($this->socket)),
                            socket_last_error($this->socket)
                        );
                        break;
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

    /**
     * Функция возвращает одну из констант self::ERROR_*
     * Параметр $errno - номер ошибки функции socket_last_error()
     * Параметр $operation - номер операции; 1 = запись, 0 = чтение.
     *
     * @param int $errno
     * @param int $operation
     * @return int
     */
    protected function errorType(int $errno, int $operation): int
    {
        if ($errno === 0) {
            return self::ERROR_NOTHING;
        } elseif (isset(self::$catchableErrors[$errno])) {
            Log::log(sprintf(
                'Socket catch error %s at %s: %d',
                socket_strerror($errno),
                $operation ? 'WRITING' : 'READING',
                $errno
            ));
            return self::$catchableErrors[$errno];
        } else {
            Log::log(sprintf('Unknown socket error %d: %s', $errno, socket_strerror($errno)));
            return self::ERROR_UNKNOWN;
        }
    }

    /**
     * Функция проверяет, установлены ли некоторые константы обрабатываемых ошибок сокетов.
     */
    private function checkConstants()
    {
        if (!empty(self::$catchableErrors)) return;
        foreach (self::ERRORS_KNOW as $const => $selfType) {
            if (defined($const)) {
                self::$catchableErrors[constant($const)] = $selfType;
            }
        }
    }
}
