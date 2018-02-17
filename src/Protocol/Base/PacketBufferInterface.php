<?php

namespace Esockets\Protocol\Base;

interface PacketBufferInterface
{
    /**
     * Сброс буферов в изначальное состояние.
     *
     * @return mixed
     */
    public function reset();
}
