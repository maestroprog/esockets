<?php

namespace Esockets\base;

abstract class AbstractClient implements ConnectorInterface
{
    /**
     * Вернет адрес сервера, к которому подключени клиент.
     *
     * @return AbstractAddress
     */
    abstract public function getServerAddress(): AbstractAddress;

    /**
     * Вернет адрес клиента, который подключен к серверу.
     *
     * @return AbstractAddress
     */
    abstract public function getClientAddress(): AbstractAddress;
}
