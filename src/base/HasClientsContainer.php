<?php

namespace Esockets\base;

/**
 * Интерфейс поддержки контейнера клиентов.
 * Данный интерфейс предназначен для сервера,
 * т.к. сервер может иметь несколько подключенных клиентов.
 */
interface HasClientsContainer
{
    /**
     * @return ClientsContainerInterface
     */
    public function getClientsContainer(): ClientsContainerInterface;
}
