<?php

namespace Esockets\protocol;

use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\exception\ReadException;
use Esockets\base\exception\SendException;
use Esockets\base\IoAwareInterface;
use Esockets\base\PingSupportInterface;
use Esockets\base\PingPacket;
use Esockets\base\AbstractProtocol;

/**
 * "Легкий" протокол.
 */
final class Easy extends AbstractProtocol implements PingSupportInterface
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


    private $eventReceive;
    private $eventPong;

    /**
     * @inheritDoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);

        $this->eventReceive = new CallbackEventsContainer();
    }


    public function read()
    {
        $data = $this->returnRead();
        if (is_null($data)) {
            return;
        }
        if (!is_callable($this->eventReceive)) {
            throw new \LogicException('OnReceive handler must be assigned.');
        }
        call_user_func($this->eventReceive, $data);
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        $result = null;
        if (($data = $this->provider->read(5, false)) !== false) {
            list($length, $flag) = array_values(unpack('Nvalue0/Cvalue1', $data));
            if ($length > 0) {
                if (($data = $this->provider->read($length, true)) !== false) {

                } else {
                    throw new ReadException('Cannot retrieve data', ReadException::ERROR_FAIL);
                }
            } else {
                throw new ReadException(
                    sprintf('Not enough length: %d bytes', $length),
                    ReadException::ERROR_PROTOCOL
                );
            }
            $result = $this->unpack($data, $flag);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function onReceive(callable $callback): CallbackEvent
    {
        return $this->eventReceive->addEvent(CallbackEvent::create($callback));
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        if ($raw = $this->pack($data)) {
            return $this->provider->send($raw);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function ping(PingPacket $pingPacket)
    {
        $this->send($pingPacket);
    }

    /**
     * @inheritDoc
     */
    public function pong(callable $pongReceived)
    {
        $this->eventPong = $pongReceived;
    }

    private function pack(&$data): string
    {
        $flag = 0;
        switch (gettype($data)) {
            case 'boolean':
                throw new SendException('Boolean data type cannot be transmitted.');
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
                if ($data instanceof PingPacket) {
                    $flag = self::DATA_INT | self::DATA_PING_PONG;
                    if (!$data->isResponse()) {
                        $flag |= self::DATA_CONTROL;
                    }
                    $data = $data->getValue();
                } else {
                    //$flag = self::DATA_EXTENDED | self::DATA_JSON;
                    throw new SendException('Values of type Object cannot be transmitted on current Net version.');
                }
                break;
            case 'resource':
                throw new SendException('Values of type Resource cannot be transmitted on current Net version.');
            case 'NULL':
                throw new SendException('Null data type cannot be transmitted.');
            case 'unknown type':
                throw new SendException('Values of Unknown type cannot be transmitted on current Net version.');
            default:
                $flag |= self::DATA_STRING;
        }
        if ($flag & self::DATA_JSON) {
            $raw = json_encode($data);
        } else {
            $raw = $data;
        }
        // начиная с этого момента исходная "$data" становится "$raw"
        $length = strlen($raw);
        if ($length >= 0xffff) { // 65535 bytes
            throw new SendException('Big data size to send! I can split it\'s');
            // кто-то попытался передать более 64 КБ за раз, выдаем ошибку
            //...пока что
        } else {
            $length = strlen($raw);
            $raw = pack('NCa*', $length, $flag, $raw);
            return $raw;
        }
    }

    /**
     * Распаковывает принятые из сокета данные.
     * Возвращает false если распаковка не удалась,
     * null если данные не были распакованы по неизвестным причинам.
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

        if ($flag & self::DATA_PING_PONG) {
            if ($flag & self::DATA_CONTROL) {
                $data = PingPacket::response($data);
                $this->send($data);
                return null;
            } else {
                return PingPacket::request($data);
            }
        }
        return $data;
    }
}
