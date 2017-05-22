<?php

namespace Esockets\base;

use Esockets\base\exception\ReadException;

/**
 * Интерфейс поддержки чтения данных двумя разными способами:
 * 1. @see self::read чтение и вызов @see CallbackEvent отвечающий за принятие данных,
 * 2. @see self::returnRead чтение и возврат прочитанных данных из метода;
 *    если читать нечего, то метод вернёт NULL.
 */
interface ReaderInterface
{
    /**
     * It reads all the data as they become available,
     * and it calls the handler assigned in @see self::onReceive.
     *
     * Он считывает все данные по мере их появления,
     * И вызывает обработчик, назначенный в @see self::onReceive.
     *
     * Вернёт true, если удалось прочитать пакет данных, и false, если не удалось.
     *
     * @return bool
     * @throws ReadException
     */
    public function read(): bool;

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
