<?php

namespace Esockets\base;

/**
 * Широковещательный интерфейс.
 */
interface BroadcastingInterface
{
    /**
     * Метод отправляет данные всем присоединённым клиентам.
     * Вернёт true, если отправка удалась,
     * и вернёт false, если ни одному клиенту не удалось отправить данные.
     *
     * @param $data
     * @return bool
     */
    public function sendToAll($data): bool;

    /**
     * Отключает всех пиров, оставляя входящее соединение открытым.
     *
     * @return void
     */
    public function disconnectAll();

    /**
     * Назначает обработчик события отсоединения всех пиров.
     * Обработчик вызывается при отсоединении последнего подключенного пира.
     *
     * @param callable $callback
     * @return CallbackEvent
     */
    public function onDisconnectAll(callable $callback): CallbackEvent;
}
