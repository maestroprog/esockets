<?php

namespace Esockets\base;

abstract class AbstractClient implements ConnectorInterface, IoAwareInterface
{
    /**
     * Вернет адрес пира, к которому подключени клиент.
     *
     * @return AbstractAddress
     */
    abstract public function getPeerAddress(): AbstractAddress;

    /**
     * Вернет адрес клиента, который подключен к серверу.
     *
     * @return AbstractAddress
     */
    abstract public function getClientAddress(): AbstractAddress;
}
