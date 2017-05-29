<?php

namespace Esockets\base;

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
    public function block();

    /**
     * Устанавливает неблокирующий режим работы соединения.
     *
     * @return void
     */
    public function unblock();
}
