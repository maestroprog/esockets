<?php

namespace Esockets\base;


interface PingInterface
{
    public function ping();

    public function pong(PingPacket $pingData);
}