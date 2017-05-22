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
 * Протокол-обёртка над php-шными структурами данных,
 * передаваемых по сети.
 * В задачи данного протокола входит:
 *  - сериализация (json-изация) отправляемых структур данных
 *  - партицирование получившихся сериализованных данных todo
 *  - нумерация получившихся партиций, и их сборка после получения (только для UDP) todo
 *  - десериализация полученных на другой стороне данных.
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

    const HEADER_LENGTH = 5;

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

    public function read(): bool
    {
        $data = $this->returnRead();
        if (is_null($data)) {
            return false;
        }
        $this->eventReceive->callEvents($data);
        return true;
    }

    private $buffer = '';

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        $result = null;
        $readData = $this->provider->read($this->provider->getMaxPacketSize(), false);
        if (!is_null($readData)) {
            $this->buffer .= $readData;
        }
        $bufferLength = strlen($this->buffer);
        if ($bufferLength) {
            list($length, $flag) = array_values(unpack(
                'Nvalue0/Cvalue1',
                substr($this->buffer, 0, self::HEADER_LENGTH)
            ));
            $data = substr($this->buffer, self::HEADER_LENGTH, $length);
            if ($length > 0 && !$data) {
                throw new ReadException('Cannot retrieve data', ReadException::ERROR_FAIL);
            } elseif (strlen($data) < $length) {
                throw new ReadException(
                    sprintf('Not enough length: %d bytes', $length),
                    ReadException::ERROR_PROTOCOL
                );
            } else {
                $offset = self::HEADER_LENGTH + $length;
                if ($offset === $bufferLength) {
                    $this->buffer = '';
                } else {
                    $this->buffer = substr($this->buffer, $offset, $bufferLength - $offset);
                }
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
        if ($length - self::HEADER_LENGTH >= $this->provider->getMaxPacketSize()) { // todo 65535 bytes
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
