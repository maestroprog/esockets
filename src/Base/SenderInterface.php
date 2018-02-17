<?php

namespace Esockets\Base;

use Esockets\Base\Exception\SendException;

/**
 * Интерфейс поддержки отправки данных.
 */
interface SenderInterface
{
    /**
     * Отправляет данные в подключение.
     *
     * @param $data
     *
     * @return bool
     *
     * @throws SendException
     */
    public function send($data): bool;
}
