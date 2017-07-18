<?php

namespace Esockets\protocol;

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEventListener;
use Esockets\base\exception\ReadException;
use Esockets\base\exception\SendException;
use Esockets\base\IoAwareInterface;
use Esockets\base\PingPacket;
use Esockets\base\PingSupportInterface;

/**
 * Протокол-обёртка над php-шными структурами данных,
 * передаваемых по сети.
 * В задачи данного протокола входит:
 *  - сериализация (json-изация) отправляемых структур данных
 *  - уведомления о доставке (нужно для UDP) todo
 *  - десериализация полученных на другой стороне данных.
 */
final class EasyDatagram extends AbstractProtocol implements PingSupportInterface
{
    /**
     * ID последнего отправленного пакета.
     *
     * @var int
     */
    private $lastSendPacketId = 1;

    /**
     * @inheritDoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        $result = null;
        $data = $this->provider->read($this->provider->getReadBufferSize(), false);
        if (!is_null($data)) {
            $result = $this->unpack($data);
            if ($result instanceof PingPacket) {
                if (!$result->isResponse()) {
                    $this->send(PingPacket::response($result->getValue()));
                } else {
                    $this->pongReceived($result);
                }
                $result = null;
            }
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        $packetId = $this->lastSendPacketId++;
        $packet = $this->pack($packetId, $data);
        if (!$this->provider->send($packet)) {
            return false;
        }
        return true;
    }

    /**
     * Пакует данные в пакеты, и возвращает массив пакетов,
     * которые можно отправлять в любом порядке.
     *
     * @param int $packetId
     * @param mixed $data
     * @return string
     * @throws SendException
     */
    private function pack(int $packetId, $data): string
    {
        if (!$this->canSend($type = gettype($data))) {
            throw new SendException('Cannot send data of type "' . $type . '".');
        }
        if ($type === 'object') {
            $data = serialize($data);
        }
        $data = json_encode([
            'id' => $packetId,
            'type' => $type,
            'data' => $data,
        ]);

        $maxPacketSize = $this->provider->getMaxPacketSizeForWriting();
        if ($maxPacketSize > 0 && strlen($data) > $maxPacketSize) {
            throw new SendException('Large size of data to send! Please break it into your code.');
        }
        return $data;
    }

    /**
     * Распаковывает принятые из сокета данные.
     * Возвращает null если данные не были распакованы по неизвестным причинам.
     *
     * @param string $raw
     * @return mixed
     * @throws ReadException
     */
    private function unpack(string $raw)
    {
        if (is_null($data = json_decode($raw, true))) {
            throw new ReadException('Cannot decode json packet.');
        }
        if (!isset($data['type']) || !isset($data['data']) || !isset($data['id'])) {
            throw new ReadException('Invalid packet received.');
        }
        switch ($data['type']) {
            case 'string':
            case 'array':
                $data = $data['data'];
                break;
            case 'integer':
                $data = (int)$data['data'];
                break;
            case 'float':
                $data = (float)$data['data'];
                break;
            case 'double':
                $data = (double)$data['data'];
                break;
            case 'object':
                $data = unserialize($data['data']);
                break;
            default:
                throw new ReadException('Unknown packet data type "' . $data['type'] . '".');
        }
        return $data;
    }

    /**
     * Выбирает флаг соотетствующий типу данных.
     *
     * @param string $dataType
     * @return int
     * @throws SendException
     */
    private function canSend(string $dataType): int
    {
        switch ($dataType) {
            case 'boolean':
            case 'resource':
            case 'NULL':
            case 'unknown type':
                return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function ping(PingPacket $pingPacket)
    {
        $this->send($pingPacket);
    }

    private $pongCallback;

    /**
     * @inheritDoc
     */
    public function pong(callable $pongReceived)
    {
        $this->pongCallback = $pongReceived;
    }

    private function pongReceived(PingPacket $pong)
    {
        if (!is_null($this->pongCallback)) {
            call_user_func($this->pongCallback, $pong);
            $this->pongCallback = null;
        }
    }
}
