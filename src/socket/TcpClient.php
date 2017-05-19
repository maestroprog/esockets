<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\exception\ConnectionException;
use Esockets\base\exception\ReadException;
use Esockets\base\IoAwareInterface;
use Esockets\base\PingPacket;
use Esockets\debug\Log;

final class TcpClient extends AbstractSocketClient implements IoAwareInterface
{
    /**
     * @var bool connection state
     */
    protected $connected = false;
    protected $eventDisconnect;

    public function connect(AbstractAddress $serverAddress)
    {
        if ($this->connected) {
            throw new \LogicException('Socket is already connected.');
        }
        if ($this->isIpAddress() && $serverAddress instanceof Ipv4Address) {
            if (socket_connect($this->socket, $serverAddress->getIp(), $serverAddress->getPort())) {
                $this->connected = true;
            }
        } elseif ($this->isUnixAddress() && $serverAddress instanceof UnixAddress) {
            if (socket_connect($this->socket, $serverAddress->getSockPath())) {
                $this->connected = true;
            }
        } else {
            throw new \LogicException('Unknown socket address.');
        }
        if (!$this->connected) {
            $this->errorHandler->handleError();
        }
    }

    public function read(int $length, $force)
    {
        $buffer = '';
        $try = 0;
        do {
            $data = socket_read($this->socket, $length);
            if ($data === false || $data === '') {
                switch ($this->errorHandler->getErrorType(socket_last_error($this->socket), self::OP_READ)) {
                    case SocketErrorHandler::ERROR_NOTHING:
                        if (PHP_OS !== 'WINNT') {
                            $this->disconnect();
                        }
                        return false;
                        break;
                    case SocketErrorHandler::ERROR_AGAIN:
                        if ($data === false) {
                            // todo это вроде как только для unix систем
                            return false;
                        } elseif (!strlen($data) || $try++ > 100) {
                            //todo
                            $this->disconnect(); // TODO тут тоже закрыто. выяснить почему???
                            return false;
                        } elseif ($length > 0) {
                            usleep(self::SOCKET_WAIT);
                        }
                        continue 2;
                        break;
                    case SocketErrorHandler::ERROR_SKIP:
                        return false;

                    case SocketErrorHandler::ERROR_FATAL:
                        $this->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        return false;

                    case SocketErrorHandler::ERROR_UNKNOWN:
                        throw new \Exception(
                            'Socket read error: '
                            . socket_strerror(socket_last_error($this->socket)),
                            socket_last_error($this->socket)
                        );
                        break;
                }
            } else {
                $buffer .= $data;
                $length -= strlen($data);
                $try = 0; // обнуляем счетчик попыток чтения
                if ($length > 0) {
                    usleep(self::SOCKET_WAIT);
                }
            }
        } while ($force && $length > 0);
        return $buffer;
    }

    // todo есть некоторые непонятные моменты в приеме/отправке данных. надо потестить!
    public function send($data): bool
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

                switch ($this->errorHandler->getErrorType(socket_last_error($this->socket), self::OP_WRITE)) {
                case
                    SocketErrorHandler::ERROR_NOTHING:
                        var_dump($wrote);
                        throw new \Exception(
                            'Socket write no error: '
                            . socket_strerror(socket_last_error($this->socket)),
                            socket_last_error($this->socket)
                        );
                        break;
                    case SocketErrorHandler::ERROR_AGAIN:
                        usleep(self::SOCKET_WAIT);
                        continue 2;
                        break;
                    case SocketErrorHandler::ERROR_SKIP:
                        return false;

                    case SocketErrorHandler::ERROR_FATAL:
                        $this->disconnect(); // принудительно обрываем соединение, сбрасываем дескрипторы
                        return false;

                    case SocketErrorHandler::ERROR_UNKNOWN:
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

    public function getReceivedBytesCount(): int
    {
        // TODO: Implement getReceivedBytesCount() method.
    }

    public function getReceivedPacketCount(): int
    {
        // TODO: Implement getReceivedPacketCount() method.
    }

    public function getTransmittedBytesCount(): int
    {
        // TODO: Implement getTransmittedBytesCount() method.
    }

    public function getTransmittedPacketCount(): int
    {
        // TODO: Implement getTransmittedPacketCount() method.
    }
}