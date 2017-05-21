<?php

namespace Esockets\socket;

use Esockets\base\exception\ConnectionException;
use Esockets\debug\Log;

final class SocketErrorHandler
{
    /** Константы внутренних ошибок. */
    const ERROR_NOTHING = 0;    // нет ошибки
    const ERROR_AGAIN = 1;      // ошибка, просьба повторить операцию
    const ERROR_SKIP = 2;       // ошибка, просьба пропустить операцию
    const ERROR_FATAL = 4;      // фатальная ошибка
    const ERROR_UNKNOWN = 8;    // неизвестная необрабатываемая ошибка

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

    private $socket;

    public function __construct($socket = null)
    {
        if (!is_null($socket)) {
            if (!is_resource($socket)) {
                throw new ConnectionException('Socket don\'t is resource');
            } elseif (get_resource_type($socket) !== 'Socket') {
                throw new ConnectionException('Unknown resource type: ' . get_resource_type($socket));
            }
        }
        $this->socket = $socket;
        $this->checkConstants();
    }

    public function setSocket($socket)
    {
        if (!is_resource($socket)) {
            throw new ConnectionException('Socket don\'t is resource');
        } elseif (get_resource_type($socket) !== 'Socket') {
            throw new ConnectionException('Unknown resource type: ' . get_resource_type($socket));
        }
        $this->socket = $socket;
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
    public function getErrorType(int $errno, int $operation): int
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

    public function handleError()
    {
        if (is_resource($this->socket)) {
            $error = socket_last_error($this->socket);
        } else {
            $error = socket_last_error();
        }
        $errorMessage = socket_strerror($error);
        switch ($error) {
            case SOCKET_EADDRINUSE:
                // если адрес используется,
            case SOCKET_ECONNREFUSED:
                // если отсутствует файл сокета,
            case SOCKET_ENOENT:
                // либо соединиться со слушающим сокетом не удалось
                throw new ConnectionException($errorMessage, ConnectionException::ERROR_FAIL_CONNECT);
            // break
            default:
                // в иных случаях кидаем исключение
                throw new ConnectionException(
                    'Unknown connection error: ' . $errorMessage,
                    ConnectionException::ERROR_UNKNOWN
                );
        }
    }
}
