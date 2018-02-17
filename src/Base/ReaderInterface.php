<?php

namespace Esockets\Base;

use Esockets\Base\Exception\ReadException;

/**
 * Интерфейс поддержки чтения данных двумя разными способами:
 * 1. @see self::read() чтение и вызов @see CallbackEventListener отвечающий за принятие данных,
 *    если чтение прошло успешно, то метод вернёт true.
 * 2. @see self::returnRead чтение и возврат прочитанных данных;
 *    если читать нечего, то метод вернёт NULL.
 */
interface ReaderInterface
{
    /**
     * It reads all the data as they become available,
     * and it calls the handler assigned in @see ReaderInterface::onReceive.
     *
     * Метод считывает все данные по мере их появления,
     * И вызывает обработчик, назначенный в @see ReaderInterface::onReceive().
     *
     * Вернёт true, если удалось прочитать пакет данных, иначе false.
     *
     * @return bool
     * @throws ReadException
     */
    public function read(): bool;

    /**
     * Read and returns the read data.
     * Returns void if there is no data to read.
     *
     * Метод читает и возвращает прочитанные данные, если такие имелись.
     *
     * @return mixed|void
     * @throws ReadException
     */
    public function returnRead();

    /**
     * Assigns handler for received data.
     *
     * Назначает обработчик для принятых данных.
     *
     * @param callable $callback
     *
     * @return CallbackEventListener
     */
    public function onReceive(callable $callback): CallbackEventListener;
}
