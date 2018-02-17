<?php

namespace Esockets\Base;

use Esockets\Base\Exception\ConnectionFactoryException;

abstract class AbstractConnectionFactory
{
    /**
     * @param array $params
     *
     * @throws ConnectionFactoryException
     */
    abstract public function __construct(array $params = []);

    /**
     * Подготавливает и возвращает объект клиента.
     *
     * @return AbstractClient
     */
    abstract public function makeClient(): AbstractClient;

    /**
     * Подготоваливает и возвращает объект сервера.
     *
     * @return AbstractServer
     */
    abstract public function makeServer(): AbstractServer;

    /**
     * При подключении нового клиента к серверу вызывается данная функция.
     * Эта функция создает объект клиента, передавая ему готовый ресур соединения (например сокет),
     * и возвращает созданный объект.
     *
     * @param AbstractConnectionResource $connectionResource
     *
     * @return AbstractClient
     */
    abstract public function makePeer(AbstractConnectionResource $connectionResource): AbstractClient;
}
