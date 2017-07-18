<?php

namespace Esockets\protocol\base;

interface PacketBufferInterface
{
    /**
     * Сброс буферов в изначальное состояние.
     *
     * @return mixed
     */
    public function reset();
}
