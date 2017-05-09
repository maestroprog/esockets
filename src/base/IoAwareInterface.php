<?php

namespace Esockets\base;

use Esockets\base\exception\ReadException;

/**
 * Интерфейс, описывающий возможности ввода/вывода.
 * Для обеспечения ввода-вывода необходимо его реализовать.
 * AbstractProtocol использует данный интерфейс для омбена информацией.
 */
interface IoAwareInterface
{
    /**
     * Necessarily reads a predetermined number of bytes.
     *
     * @param int $length the number of bytes to read
     * @param bool $force
     * @return mixed Received data
     *
     * @throws ReadException If can not read the data
     */
    public function read(int $length, $force);

    public function getReceivedBytesCount(): int;

    public function getReceivedPacketCount(): int;


    public function send($data): bool;

    public function getTransmittedBytesCount(): int;

    public function getTransmittedPacketCount(): int;
}
