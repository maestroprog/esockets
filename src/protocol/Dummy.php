<?php

namespace Esockets\protocol;

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEvent;

final class Dummy extends AbstractProtocol
{
    /**
     * @inheritdoc
     */
    public function read()
    {
        return $this->provider->read(0, false);
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        return $this->provider->send($data);
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        return $this->provider->read(1, false);
    }

    /**
     * @inheritDoc
     */
    public function onReceive(callable $callback): CallbackEvent
    {
        return CallbackEvent::create($callback);
    }


}
