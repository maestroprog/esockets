<?php

namespace Esockets;

class ClientStatistic
{
    private $receivedBytesCount;
    private $receivedPacketsCount;
    private $transmittedBytesCount;
    private $transmittedPacketsCount;

    public function __construct(
        int $receivedBytes,
        int $receivedPackets,
        int $transmittedBytes,
        int $transmittedPackets
    )
    {
        $this->receivedBytesCount = $receivedBytes;
        $this->receivedPacketsCount = $receivedPackets;
        $this->transmittedBytesCount = $transmittedBytes;
        $this->transmittedPacketsCount = $transmittedPackets;
    }

    public function getReceivedBytesCount(): int
    {
        return $this->receivedBytesCount;
    }

    public function getReceivedPacketsCount(): int
    {
        return $this->receivedPacketsCount;
    }

    public function getTransmittedBytesCount(): int
    {
        return $this->transmittedBytesCount;
    }

    public function getTransmittedPacketsCount(): int
    {
        return $this->transmittedPacketsCount;
    }
}
