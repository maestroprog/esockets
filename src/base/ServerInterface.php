<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 8:35
 */

namespace maestroprog\esockets\base;


interface ServerInterface extends NetInterface
{

    /**
     * @return bool
     * открывает входящее соединение
     * возвращает true при успешном открытии, false при сбое
     */
    public function connect();

    /**
     * закрывает входящее соединение
     */
    public function disconnect();

    /**
     * отключает всех пиров
     */
    public function disconnectAll();

    /**
     * слушает входящие соединения
     * вызывает обработчик события, заданный в onConnectPeer()
     */
    public function listen();

    /**
     * читает входные данные от всех пиров по очереди
     */
    public function read();

    /**
     * @return int
     * отправляет пакет данных всем пирам по очереди
     * возвращает количество успешно отправленных пакетов
     */
    public function send($data);

    /**
     * @param callable $callback
     * назначает событие соединения клиента
     */
    public function onConnectPeer(callable $callback);

    /**
     * @param callable $callback
     * назначает событие при отсоединении пира
     */
    public function onDisconnectPeer(callable $callback);

    /**
     * @param callable $callback
     * назначает событие при отсоединении всех пиров
     *
     * событие вызывается после вызоыва onDisconnectPeer
     * при отсоединении последнего подключенного пира
     */
    public function onDisconnectAll(callable $callback);
}
