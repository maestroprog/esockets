<?php

namespace Esockets\protocol;

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\IoAwareInterface;

/**
 * Не-протокол, использующийся поверх TCP или UDP.
 * Для передачи потока байт.
 */
final class Dummy extends AbstractProtocol
{
    private $eventReceive;

    /**
     * @inheritDoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);

        $this->eventReceive = new CallbackEventsContainer();
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        if (null !== ($data = $this->provider->read($this->provider->getMaxPacketSize(), false))) {
            $this->eventReceive->callEvents($data);
        }
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        if (strlen($data) > $this->provider->getMaxPacketSize()) {
            $packets = str_split($data, $this->provider->getMaxPacketSize());
            array_walk($packets, function (string $packet) {
                $this->provider->send($packet);
            });
        }
        return $this->provider->send($data);
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        return $this->provider->read($this->provider->getMaxPacketSize(), false);
    }

    /**
     * @inheritDoc
     */
    public function onReceive(callable $callback): CallbackEvent
    {
        return $this->eventReceive->addEvent(CallbackEvent::create($callback));
    }
}
