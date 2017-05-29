<?php

namespace Esockets\base;

use Esockets\Client;

/**
 * Интерфейс контейнера клиентов.
 */
interface ClientsContainerInterface
{
    /**
     * Добавляет клиента в контейнер.
     * После этого клиент становится известен серверу (как "свой").
     *
     * @param $client Client
     */
    public function add(Client $client);

    /**
     * Удаляет клиента из контейнера.
     * Обычно удаление происходит при отсоединении клиента от сервера.
     *
     * @param $client Client
     */
    public function remove(Client $client);

    /**
     * Вернёт список клиентов содержащихйся в контейнере.
     *
     * @return Client[]
     */
    public function list(): array;

    /**
     * Проверяет, находится ли указанный клиент в контейнере.
     *
     * @param Client $client
     * @return bool
     */
    public function exists(Client $client): bool;

    /**
     * Проверяет наличие клиента в контейнере по его адресу.
     *
     * @param AbstractAddress $address
     * @return bool
     */
    public function existsByAddress(AbstractAddress $address): bool;

    /**
     * Вернёт клиент по его адресу.
     *
     * @param AbstractAddress $address
     * @return Client
     * @throws \RuntimeException если клиент не найден
     */
    public function getByAddress(AbstractAddress $address): Client;
}
