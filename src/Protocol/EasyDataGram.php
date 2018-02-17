<?php

namespace Esockets\Protocol;

use Esockets\Base\AbstractProtocol;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\Exception\ReadException;
use Esockets\Base\Exception\SendException;
use Esockets\Base\IoAwareInterface;
use Esockets\Base\PingPacket;
use Esockets\Base\PingSupportInterface;

/**
 * Протокол-обёртка над php-шными структурами данных,
 * передаваемых по сети.
 * В задачи данного протокола входит:
 *  - сериализация (json-изация) отправляемых структур данных
 *  - уведомления о доставке (нужно для UDP) todo
 *  - десериализация полученных на другой стороне данных.
 */
final class EasyDataGram extends AbstractProtocol implements PingSupportInterface
{
    /**
     * ID последнего отправленного пакета.
     *
     * @var int
     */
    private $lastSendPacketId = 1;
    private $pingCallback;
    private $pongCallback;

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
        if (null !== $data) {
            $result = $this->unpack($data);
            if ($result instanceof PingPacket) {
                if (!$result->isResponse()) {
                    if (!$this->send(PingPacket::response($result->getValue()))) {
                        throw new \RuntimeException('Cannot send pong packet.');
                    }
                    $this->pingReceived($result);
                } else {
                    $this->pongReceived($result);
                }
                $result = null;
            }
        }
        return $result;
    }

    /**
     * Распаковывает принятые из сокета данные.
     * Возвращает null если данные не были распакованы по неизвестным причинам.
     *
     * @param string $raw
     *
     * @return mixed
     * @throws ReadException
     */
    private function unpack(string $raw)
    {
        if (null === ($data = json_decode($raw, true)) && json_last_error() > 0) {
            throw new ReadException('Cannot decode json packet.', ReadException::ERROR_PROTOCOL);
        }
        if (!isset($data['type']) || !isset($data['data']) || !isset($data['id'])) {
            throw new ReadException('Invalid packet received.', ReadException::ERROR_PROTOCOL);
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
                throw new ReadException(
                    sprintf(
                        'Unknown packet data type "%s".',
                        $data['type']
                    ), ReadException::ERROR_PROTOCOL
                );
        }
        return $data;
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
     *
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
     * Выбирает флаг соотетствующий типу данных.
     *
     * @param string $dataType
     *
     * @return int
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

    private function pingReceived(PingPacket $ping): void
    {
        if (null !== $this->pingCallback) {
            call_user_func($this->pingCallback, $ping);
        }
    }

    private function pongReceived(PingPacket $pong): void
    {
        if (null !== $this->pongCallback) {
            call_user_func($this->pongCallback, $pong);
            $this->pongCallback = null;
        }
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
    public function ping(PingPacket $pingPacket): void
    {
        $this->send($pingPacket);
    }

    /**
     * @inheritdoc
     */
    public function onPingReceived(callable $pingReceived): void
    {
        $this->pingCallback = $pingReceived;
    }

    /**
     * @inheritdoc
     */
    public function pong(callable $pongReceived): void
    {
        $this->pongCallback = $pongReceived;
    }
}
