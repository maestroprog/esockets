<?php

namespace Esockets\base;

/**
 * Широковещательный интерфейс.
 */
interface BroadcastingInterface
{
    /**
     * Метод заставляет прочитать поступившие данные (при их наличии) ото всех клиентов.
     *
     * @return void
     */
    public function read();

    /**
     * Метод отправляет данные всем присоединённым клиентам.
     * Вернёт true, если отправка удалась,
     * и вернёт false, если ни одному клиенту не удалось отправить данные.
     *
     * @param $data
     * @return bool
     */
    public function sendToAll($data): bool;
}
