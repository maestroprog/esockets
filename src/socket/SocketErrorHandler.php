<?php

namespace Esockets\socket;

use Esockets\base\exception\ConnectionException;
use Esockets\debug\Log;

/**
 * Общий обработчик ошибок, возникающих при работе с сокетами.
 * Выполняет две функции: определение деструктивной степени ошибки,
 * и выброс исключения @see ConnectionException в случае возникновения серьезной ошибки.
 *
 * Для каждого объекта с подключением создается собственный объект handler-а...
 */
final class SocketErrorHandler
{
    /** Константы внутренних ошибок. */
    const ERROR_NOTHING = 0;    // нет ошибки
    const ERROR_AGAIN = 1;      // ошибка, просьба повторить операцию
    const ERROR_SKIP = 2;       // ошибка, просьба пропустить операцию
    const ERROR_DISCONNECTED = 4;      // фатальная ошибка
    const ERROR_UNKNOWN = 8;    // неизвестная необрабатываемая ошибка

    /**
     * @var array Известные и обрабатываемые ошибки сокетов
     */
    protected static $catchableErrors = [];

    /** Список известных ошибок для настройки обработчика */
    const ERRORS_KNOW = [
        'SOCKET_EWOULDBLOCK' => self::ERROR_NOTHING,
        'SOCKET_EMSGSIZE' => self::ERROR_NOTHING,
        'SOCKET_EAGAIN' => self::ERROR_AGAIN,
        'SOCKET_TRY_AGAIN' => self::ERROR_AGAIN,
        'SOCKET_EPIPE' => self::ERROR_DISCONNECTED,
        'SOCKET_ENOTCONN' => self::ERROR_DISCONNECTED,
        'SOCKET_ECONNABORTED' => self::ERROR_DISCONNECTED,
        'SOCKET_ECONNRESET' => self::ERROR_DISCONNECTED,
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
        if (!defined('SOCKET_ENOENT')) {
            define('SOCKET_ENOENT', 2);
        }
        if (!empty(self::$catchableErrors)) return;
        foreach (self::ERRORS_KNOW as $const => $selfType) {
            if (defined($const)) {
                self::$catchableErrors[constant($const)] = $selfType;
            }
        }
    }

    /**
     * Функция возвращает одну из констант self::ERROR_*
     * Параметр $operation - номер операции; 1 = запись, 0 = чтение.
     *
     * @param int $operation
     * @return int
     */
    public function getErrorLevel(int $operation): int
    {
        if (!is_resource($this->socket) || !get_resource_type($this->socket) === 'Socket') {
            $errno = SOCKET_EPIPE;
        } else {
            $errno = socket_last_error($this->socket);
        }
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
        if (PHP_OS === 'WINNT') {
            $errorMessage = mb_convert_encoding($errorMessage, 'utf-8', 'windows-1251');
        }
        switch ($error) {
            case 0:
            case SOCKET_EWOULDBLOCK: // операции на незаблокированном сокет
            case SOCKET_EMSGSIZE:
            case SOCKET_ECONNRESET:
                // ничего не делаем.
                break;
            case SOCKET_EADDRINUSE: // если адрес используется,
            case SOCKET_ECONNREFUSED: // соединиться со слушающим сокетом не удалось
            case SOCKET_ENOENT: // если отсутствует файл сокета,
                throw new ConnectionException($errorMessage, ConnectionException::ERROR_FAIL_CONNECT);
            // break
            default: // в иных случаях кидаем исключение
                throw new ConnectionException(
                    'Unknown connection error ' . $error . ': ' . $errorMessage,
                    ConnectionException::ERROR_UNKNOWN
                );
        }
    }
}
