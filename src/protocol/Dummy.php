<?php

namespace Esockets\protocol;


use Esockets\protocol\base\UseIO;

class Dummy extends UseIO
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
