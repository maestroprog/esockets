<?php

namespace Esockets\base;


use Esockets\base\exception\ReadException;

interface ReaderInterface
{
    const ERROR_EMPTY = 0; // nothing to read
    const ERROR_FAIL = 1; // not read to the end

    /**
     * It reads the data as they become available,
     * and returns the data, if necessary.
     *
     * @return mixed|void
     */
    public function read();

    /**
     * Read and returns the read data.
     * Returns void if there is no data to read.
     *
     * @return mixed|void
     */
    public function returnRead();

    /**
     * Necessarily reads a predetermined number of bytes.
     *
     * @param int $length the number of bytes to read
     *
     * @return mixed Received data
     *
     * @throws ReadException If can not read the data
     */
    public function needRead(int $length);

    /**
     * Assigns handler for received data.
     *
     * @param callable $callback
     *
     * @return void
     */
    public function onReceive(callable $callback);


    public function getReceivedBytesCount(): int;

    public function getReceivedPacketCount(): int;
}