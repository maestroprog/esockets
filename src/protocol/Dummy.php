<?php

namespace Esockets\protocol;

use Esockets\base\AbstractProtocol;

final class Dummy extends AbstractProtocol
{
    /**
     * @inheritdoc
     */
    public function read(bool $need = false)
    {
        return $this->provider->read(0, $need);
    }

    /**
     * @inheritdoc
     */
    public function send(&$data): bool
    {
        return $this->provider->send($data);
    }
}
