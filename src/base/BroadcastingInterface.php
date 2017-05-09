<?php

namespace Esockets\base;

interface BroadcastingInterface
{
    public function sendToAll($data);
}