<?php

namespace Esockets\base;

/**
 * Интерфейс поддержки отправки данных.
 */
interface SenderInterface
{
    public function send($data): bool;
}
