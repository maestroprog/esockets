<?php

namespace Esockets\Base;

abstract class AbstractConnectionResource
{
    protected $resource;

    /**
     * Возвращает ID соединения или ресурса соединения.
     *
     * @return int
     */
    abstract public function getId(): int;

    /**
     * Возвращает ресурс (или указатель) соединения.
     * Обычно, запрашивать ресурс нет необходимости,
     * но это может пригодиться для выполнении
     * низкоуровневых операций (таких как socket_select(), fwrite(), и др.)
     * вне объектов Esockets.
     *
     * @return mixed
     */
    abstract public function getResource();
}
