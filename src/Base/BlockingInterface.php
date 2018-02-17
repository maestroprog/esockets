<?php

namespace Esockets\Base;

/**
 * Интерфейс поддержки блокировок соединений.
 */
interface BlockingInterface
{
    /**
     * Устанавливает блокирующий режим работы соединения.
     *
     * @return void
     */
    public function block(): void;

    /**
     * Устанавливает неблокирующий режим работы соединения.
     *
     * @return void
     */
    public function unblock(): void;
}
