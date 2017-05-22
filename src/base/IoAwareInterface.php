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
     * Метод должен вернуть максимальный/рекомендуемый размер
     * пакета данных в байтах для данной среды передачи.
     * Если метод вернёт "0", то это будет оззначить "безлимитный размер пакета".
     *
     * @return int
     */
    public function getMaxPacketSize(): int;

    /**
     * Necessarily reads a predetermined number of bytes.
     *
     * @param int $length the number of bytes to read
     * @param bool $force
     * @return mixed|null Received data
     *
     * @throws ReadException If can not read the data
     */
    public function read(int $length, bool $force);

    public function getReceivedBytesCount(): int;

    public function getReceivedPacketCount(): int;

    public function send($data): bool;

    public function getTransmittedBytesCount(): int;

    public function getTransmittedPacketCount(): int;
}
