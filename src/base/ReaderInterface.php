<?php

namespace Esockets\base;

use Esockets\base\exception\ReadException;

interface ReaderInterface
{
    /**
     * It reads the data as they become available,
     * and it calls the handler assigned in @see self::onReceive.
     *
     * @return mixed|void
     * @throws ReadException
     */
    public function read();

    /**
     * Read and returns the read data.
     * Returns void if there is no data to read.
     *
     * @return mixed|void
     * @throws ReadException
     */
    public function returnRead();

    /**
     * Assigns handler for received data.
     *
     * @param callable $callback
     * @return CallbackEvent
     */
    public function onReceive(callable $callback): CallbackEvent;
}
