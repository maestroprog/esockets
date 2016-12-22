<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 20:41
 */

namespace Esockets\protocol;

use Esockets\protocol\base\UseIO;
use Esockets\debug\Log;

class Easy extends UseIO
{
    const DATA_RAW = 0;
    const DATA_JSON = 1;
    const DATA_INT = 2;
    const DATA_FLOAT = 4;
    const DATA_STRING = 8;
    const DATA_ARRAY = 16;
    const DATA_EXTENDED = 32; // reserved for objects
    const DATA_PING_PONG = 64; // reserved
    const DATA_CONTROL = 128;

    public function read(bool $need = false)
    {
        // read message meta
        if (($data = $this->provider->read(5)) !== false) {
            list($length, $flag) = array_values(unpack('Nvalue0/Cvalue1', $data));
            if ($length > 0) {
                if (($data = $this->provider->read($length, true)) !== false) {

                } else {
                    throw new \Exception('Cannot retrieve data');
                }
            } else {
                throw new \Exception(sprintf('Not enough length: %d bytes', $length));
            }

            return $this->unpack($data, $flag);
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function send(&$data): bool
    {
        if ($raw = $this->pack($data)) {
            return $this->provider->send($raw);
        }
        return false;
    }

    private function pack(&$data): string
    {
        $flag = 0;
        switch (gettype($data)) {
            case 'boolean':
                trigger_error('Boolean data type cannot be transmitted', E_USER_WARNING);
                return false;
                break;
            case 'integer':
                $flag = self::DATA_INT;
                break;
            case 'double':
                $flag = self::DATA_FLOAT;
                break;
            case 'array':
                $flag = self::DATA_ARRAY | self::DATA_JSON;
                break;
            case 'object':
                $flag = self::DATA_EXTENDED | self::DATA_JSON;
                trigger_error('Values of type Object cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            case 'resource':
                trigger_error('Values of type Resource cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            case 'NULL':
                trigger_error('Null data type cannot be transmitted', E_USER_WARNING);
                return false;
                break;
            case 'unknown type':
                trigger_error('Values of Unknown type cannot be transmitted on current Net version', E_USER_WARNING);
                return false;
                break;
            default:
                $flag |= self::DATA_STRING;
        }
        if ($flag & self::DATA_JSON)
            $raw = json_encode($data);
        else
            $raw = $data;
        // начиная с этого момента исходная "$data" становится "$raw"
        $length = strlen($raw);
        if ($length >= 0xffff) { // 65535 bytes
            trigger_error('Big data size to send! I can split it\'s', E_USER_ERROR);
            // кто-то попытался передать более 64 КБ за раз, выдаем ошибку
            //...пока что
            return false;
        } else {
            $length = strlen($raw);
            $raw = pack('NCa*', $length, $flag, $raw);
            return $raw;
        }
    }

    /**
     * Ф-я распаковывает принятые из сокета данные.
     * Возвращает false если распаковка не удалась,
     *      null если данные не были распакованы по неизвестным причинам
     *      todo PingMessage
     *
     * @param string $raw
     * @param int $flag
     * @return mixed
     */
    private function unpack(string $raw, int $flag)
    {
        $data = null;
        if ($flag & self::DATA_JSON) {
            $data = json_decode($raw, $flag & self::DATA_ARRAY ? true : false);
        } elseif ($flag & self::DATA_INT) {
            $data = (int)$raw;
        } elseif ($flag & self::DATA_FLOAT) {
            $data = (float)$raw;
        } else {
            $data = $raw; // simple string
        }
        /*
         * todo раскомминтировать и перепилить
         * @see "todo PingMessage"
         * if ($flag & self::DATA_CONTROL) {
            // control message parser
            // @TODO
            if ($flag & self::DATA_PING_PONG) {
                if (is_callable($this->event_pong)) {
                    call_user_func($this->event_pong, $raw);
                } else {
                    Log::log('pong received');
                }
            }
        } elseif ($flag & self::DATA_PING_PONG) {
            // отправляем исходные данные "pong" с исходным форматом, дополнительно устанавливая флаг DATA_CONTROL
            $this->_send($raw, $flag | self::DATA_CONTROL);
            Log::log('ping received and pong sended');
            return;
        }*/
        return $data;
    }
}
