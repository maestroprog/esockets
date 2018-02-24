<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractAddress;
use Esockets\Base\Exception\SendException;

/**
 * Класс TCP клиента.
 * Может использоваться как в качестве обхекта клиента,
 * так и в качестве объекта пира (серверного сокета, взаимодействующего с удалённым клиентом).
 */
final class TcpClient extends AbstractSocketClient
{
    private $receiveBuffer;

    /**
     * @inheritdoc
     */
    public function connect(AbstractAddress $serverAddress): void
    {
        if ($this->connected) {
            throw new \LogicException('Socket is already connected.');
        }
        $this->createSocket();
        $this->serverAddress = $serverAddress;
        if ($this->isIpAddress() && $serverAddress instanceof Ipv4Address) {
            if (socket_connect($this->socket, $serverAddress->getIp(), $serverAddress->getPort())) {
                $this->connected = true;
            }
        } elseif ($this->isUnixAddress() && $serverAddress instanceof UnixAddress) {
            if (socket_connect($this->socket, $serverAddress->getSockPath())) {
                $this->connected = true;
            }
        } else {
            throw new \LogicException('Unknown socket domain.');
        }

        if (!$this->connected) {
            $this->errorHandler->handleError();
        } else {
            $this->unblock();
            $this->eventConnect->call();
        }
    }

    /**
     * @inheritdoc
     */
    public function getReadBufferSize(): int
    {
        return $this->receiveBuffer
            ?? $this->receiveBuffer = (int)socket_get_option($this->socket, SOL_SOCKET, SO_RCVBUF)
                ?: 1024 * 1024;
    }

    /**
     * @inheritdoc
     */
    public function read(int $length, bool $force)
    {
        if (!$this->connected) {
            return null;
        }
        $buffer = null;
        $tryCount = 0; // количество попыток чтения
        do {
            // цикл чтения из сокета
            $data = null;
            $readBytes = socket_recv($this->socket, $data, $length, 0);
            if ($readBytes === false || $readBytes === 0) {
                // если не удалось прочитать начинаем обрабатывать возникшую ошибку
                $errorType = $this->errorHandler->getErrorLevel(self::OP_READ);
                switch ($errorType) {
                    case SocketErrorHandler::ERROR_NOTHING:
//                        if (PHP_OS === 'WINNT') {
                        if ($readBytes === 0 && $this->isConnected()) {
                            // для windows этот кейс наступает при обрыве соединения
                            $this->disconnect();
                        }
                        continue;
//                        }
                        // в общем этот кейс возникает когда нечего читать
                        return null;
                    case SocketErrorHandler::ERROR_AGAIN:
                        if ($readBytes === 0 && null === $data && PHP_OS !== 'WINNT') {
                            // for unix only
                            $this->disconnect();
                        } elseif ($tryCount++ > 10 && $length > 0) {
                            // если не удалось прочитать нужное количество байт за несколько попыток,
                            // но уже что-то было прочитано, бросим исключение.
                            throw new \RuntimeException('Not enough bytes count: ' . $length);
                        } else {
                            // если ещё есть попытки для чтения,
                            //стоит немного подождать пока появятся данные в буфере сокета
                            usleep(self::SOCKET_WAIT);
                        }
                        continue 2; // повторяет цикл чтения с начала

                    case SocketErrorHandler::ERROR_SKIP:
                        // simply reserved
                        return null;

                    case SocketErrorHandler::ERROR_DISCONNECTED:
                        // ошибка обрыва соединения
                        $this->disconnect(); // принудительно обрываем соединение с нашей стороны

                        return null; // и выходим, тут больше нечего делать

                    case SocketErrorHandler::ERROR_UNKNOWN:
                        // попалась "неизвестная" ошибка, которая ещё не прописана в обработчике
                        throw new \RuntimeException(
                            'Socket read error: '
                            . socket_strerror(socket_last_error($this->socket)),
                            socket_last_error($this->socket)
                        );
                }
            } else {
                $this->receivedBytes += $readBytes;
                $this->receivedPackets++;
                $buffer .= $data;
                $length -= $readBytes;
                $tryCount = 0; // обнуляем счетчик попыток чтения
                if ($force && $length > 0) {
                    // если нужно ещё дочитать - немного подождем
                    usleep(self::SOCKET_WAIT);
                }
            }
        } while ($force && $length > 0);

        return $buffer;
    }

    /**
     * @inheritdoc
     */
    public function getMaxPacketSizeForWriting(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        if (!$this->connected) {
            return false;
        }
        $tryCount = 0;
        $length = strlen($data);
        $written = $length; // учет количества отправленных байт
        do {
            // цикл отправки в сокет
            $wrote = socket_send($this->socket, $data, $length, 0);
            if ($wrote === false) {
                // если отправить не удалось - получим и обработем ошибку
                $errorLevel = $this->errorHandler->getErrorLevel(self::OP_WRITE);
                switch ($errorLevel) {
                    case SocketErrorHandler::ERROR_NOTHING:
                    case SocketErrorHandler::ERROR_SKIP:
                        // nothing
                    case SocketErrorHandler::ERROR_AGAIN:
                        // нужно повторить отправку подождем немного и попробуем отправить ещё раз
                        if ($tryCount++ > 10) {
                            throw new \RuntimeException('Sending failed!');
                        }
                        usleep(self::SOCKET_WAIT);
                        continue 2;

                    case SocketErrorHandler::ERROR_DISCONNECTED:
                        // ошибка обрыва соединения
                        $this->disconnect(); // принудительно обрываем соединение на нашей стороне
                        return false;

                    case SocketErrorHandler::ERROR_UNKNOWN:
                        // неизвестная ошибка, по аналогии с ошибкой в read()
                        throw new SendException(
                            'Socket write error: '
                            . socket_strerror(socket_last_error($this->socket)),
                            socket_last_error($this->socket)
                        );
                }
            } elseif ($wrote === 0) {
                if ($tryCount++ > 10) {
                    throw new SendException('Sending failed!');
                }
                usleep(self::SOCKET_WAIT);
            } else {
                if ($wrote < $length) {
                    // если данные отправлены не полностью
                    // - урежем строку до неотправленных данных
                    $length -= $wrote;
                    $data = substr($data, $wrote, $length);
                }
                $written -= $wrote;
                $this->transmittedBytes += $wrote;
                $this->transmittedPackets++;
            }
        } while ($written > 0);

        return true;
    }
}
