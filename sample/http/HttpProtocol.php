<?php

use Esockets\base\AbstractProtocol;

final class Protocol extends AbstractProtocol
{

    /**
     * It reads the data as they become available,
     * and returns the data, if necessary.
     *
     * @return mixed|void
     * @throws \Esockets\base\exception\ReadException
     */
    public function read(): bool
    {
        // TODO: Implement read() method.
    }

    /**
     * Read and returns the read data.
     * Returns void if there is no data to read.
     *
     * @return mixed|void
     * @throws \Esockets\base\exception\ReadException
     */
    public function returnRead()
    {
        // TODO: Implement returnRead() method.
    }

    /**
     * Assigns handler for received data.
     *
     * @param callable $callback
     *
     * @return void
     */
    public function onReceive(callable $callback)
    {
        // TODO: Implement onReceive() method.
    }

    public function send($data): bool
    {
        // TODO: Implement send() method.
    }
}