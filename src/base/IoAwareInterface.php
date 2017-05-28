<?php

namespace Esockets\base;

use Esockets\base\exception\ReadException;
use Esockets\base\exception\SendException;

/**
 * Интерфейс, описывающий возможности ввода/вывода.
 * Для обеспечения ввода-вывода необходимо его реализовать.
 * @see AbstractProtocol использует данный интерфейс для омбена информацией.
 */
interface IoAwareInterface
{
    /**
     * Рекомендуемый размер буфера данных для чтения.
     * Метод должен вернуть целое число > 0 (по хорошему это нужно проверять).
     *
     * @return int
     */
    public function getReadBufferSize(): int;

    /**
     * Метод должен вернуть максимально возможный размер пакета данных в байтах
     * для передачи каких-либо данных в среде.
     * Если метод вернёт "0", то это будет значить - безлимит.
     *
     * @return int
     */
    public function getMaxPacketSizeForWriting(): int;

    /**
     * Necessarily reads a predetermined number of bytes.
     *
     * Читает указанное количество байт, и возвращает прочитанное.
     * Может прочитать меньшее количество байт, если не указана опция $force.
     * Если указана опция $force, то метод не вернёт управление до тех пор,
     * пока не прочитает указанное количество байт.
     * В противном случае он бросит исключение.
     *
     * @param int $length the count of bytes to read
     * @param bool $force
     * @return mixed|null Received data
     * @throws ReadException If can not read the data
     */
    public function read(int $length, bool $force);

    /**
     * Возвращаепт количество байт прочитанных с момента подключения.
     *
     * @return int
     */
    public function getReceivedBytesCount(): int;

    /**
     * Возвращает количество выполненных с момента подключения чтений.
     *
     * @return int
     */
    public function getReceivedPacketCount(): int;

    /**
     * Метод отправляет(записывает) все указанные данные в подключение.
     * Вернёт true если отправка прошла успешно, и false при неудаче.
     * Если во время отправки произошла необрабатываемая ошибка,
     * то метод бросит исключение.
     *
     * @param $data
     * @return bool
     * @throws SendException
     */
    public function send($data): bool;

    /**
     * Возвращает количество отправленных с момента подключения байт.
     *
     * @return int
     */
    public function getTransmittedBytesCount(): int;

    /**
     * Возвращает количество выполненных с момента подключения отправок данных.
     *
     * @return int
     */
    public function getTransmittedPacketCount(): int;
}
