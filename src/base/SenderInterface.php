<?php

namespace Esockets\base;


interface SenderInterface
{
    public function send($data): bool;


    public function getTransmittedBytesCount(): int;

    public function getTransmittedPacketCount(): int;
}