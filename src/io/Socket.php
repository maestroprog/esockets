<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 20:38
 */

namespace maestroprog\esockets\io;

use maestroprog\esockets\base\Net;
use maestroprog\esockets\io\base\IOMiddleware;
use maestroprog\esockets\protocol\base\ProtocolAware;

class Socket extends IOMiddleware
{
    /** Интервал времени ожидания между попытками при чтении/записи. */
    const SOCKET_WAIT = 1;

    /**
     * @var Net
     */
    private $connection;

    /**
     * @var resource of socket
     */
    private $socket;

    public function __construct(Net $connection, ProtocolAware $provider)
    {
        $this->connection = $connection;
        $this->socket = $connection->getConnection();
    }

    public function read(int $length, bool $need = false): mixed
    {
        $buffer = '';
        $try = 0;
        while ($length > 0) {
            $data = socket_read($this->socket, $length);
            error_log('data is ' . var_export($data, true) . ' from ' . get_class($this));
            if ($data === false || $data === '') {
                switch (socket_last_error($this->connection)) {
                    case SOCKET_EAGAIN:
                        if (!strlen($buffer) && (!$need || $try++ > 100)) {
                            return false;
                        } else {
                            error_log('Socket read error: SOCKET_EAGAIN at READING');
                            usleep(self::SOCKET_WAIT);
                        }
                        break;
                    case SOCKET_EPIPE:
                    case SOCKET_ENOTCONN:
                        $this->connection->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        return false;
                    default:
                        error_log(
                            'SOCKET READ ERROR!!!'
                            . socket_last_error($this->connection)
                            . ':' . socket_strerror(socket_last_error($this->connection))
                        );
                        // todo это временно нужно для сбора инфы о том, какие ошибы бывают, и что с ними делать.
                        // p.s. конечно лучше почитать хороший мануальчик, чем плясать с бубном.
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

    public function send(string &$data)
    {
        $length = strlen($data);
        $written = 0;
        do {
            $wrote = socket_write($this->socket, $data);
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
                        $this->connection->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        break;
                    default:
                        error_log('SOCKET WRITE ERROR!!!' . socket_last_error($this->connection));
                        throw new \Exception(
                            'Socket write error: ' . socket_strerror(socket_last_error($this->connection)),
                            socket_last_error($this->connection)
                        );
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
