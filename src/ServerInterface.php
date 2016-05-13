<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 8:35
 */

namespace Esockets;


interface ServerInterface extends NetInterface
{

    /**
     * @return mixed
     * открывает входящее соединение
     */
    public function connect();

    /**
     * @return bool
     * закрывает входящее соединение
     */
    public function disconnect();

    /**
     * @return mixed
     * отключает всех пиров
     */
    public function disconnectAll();

    /**
     * @return mixed
     * слушает входящие соединения
     */
    public function listen();

    /**
     * @return mixed
     * читает входные данные от всех пиров по очереди
     */
    public function read();

    /**
     * @return mixed
     * отправляет данные всем пирам по очереди
     */
    public function send($data);

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие соединения клиента
     */
    public function onConnectPeer(callable $callback);

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие при отсоединении пира
     */
    public function onDisconnectPeer(callable $callback);

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие при отсоединении всех пиров
     */
    public function onDisconnectAll(callable $callback);
}