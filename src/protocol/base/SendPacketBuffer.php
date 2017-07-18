<?php

namespace Esockets\protocol\base;

class SendPacketBuffer implements \ArrayAccess, PacketBufferInterface
{
    const SEND_TIMEOUT = 60; // 60 seconds

    private $buffer = [];
    private $meta = [];

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->buffer = [];
        $this->meta = [];
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->buffer[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->buffer[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->addPacket($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {

        unset($this->buffer[$offset]);
    }

    protected function addPacket(int $packetId, $data)
    {
//        echo 'sended ', $packetId, PHP_EOL;
        $this->buffer[$packetId] = $data;
        $this->meta[$packetId]['time'] = time();
        $this->clean();
    }

    protected function clean()
    {
        $timeout = time() - self::SEND_TIMEOUT;
        foreach ($this->meta as $packetId => $meta) {
            if ($meta['time'] < $timeout) {
                unset($this->buffer[$packetId]);
                unset($this->meta[$packetId]);
            }
        }
    }
}
