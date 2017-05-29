<?php

namespace Esockets\protocol;

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEventListener;
use Esockets\base\Event;
use Esockets\base\IoAwareInterface;

/**
 * Фейковый протокол, использующийся как обёртка поверх TCP или UDP.
 */
final class Dummy extends AbstractProtocol
{
    private $eventReceive;

    /**
     * @inheritdoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);

        $this->eventReceive = new Event();
    }

    /**
     * @inheritdoc
     */
    public function read(): bool
    {
        if (null !== ($data = $this->provider->read($this->provider->getMaxPacketSizeForWriting(), false))) {
            $this->eventReceive->call($data);
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        $maxSize = $this->provider->getMaxPacketSizeForWriting();
        if (strlen($data) > $maxSize && $maxSize > 0) {
            $packets = str_split($data, $maxSize);
            array_walk($packets, function (string $packet) {
                $this->provider->send($packet);
            });
        }
        return $this->provider->send($data);
    }

    /**
     * @inheritdoc
     */
    public function returnRead()
    {
        return $this->provider->read($this->provider->getMaxPacketSizeForWriting(), false);
    }

    /**
     * @inheritdoc
     */
    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }
}
