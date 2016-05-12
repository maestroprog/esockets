<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 13:04
 */

namespace Esockets;


interface NetInterface
{
    /**
     * @return bool
     * соединяет с сетью
     */
    public function connect();

    /**
     * @return bool
     * отсоединяет от сети
     */
    public function disconnect();

    /**
     * @return mixed
     * читает поступившие данные из сети
     */
    public function read();

    /**
     * @return bool
     * отправляет данные в сеть
     */
    public function send($data);

    /**
     * @return
     * пингует соединение
     */
    public function ping();

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие при отсоединении
     */
    public function onDisconnect(callable $callback);

    /**
     * @return mixed
     * вызывает событие при отсоединении
     */
    function _onDisconnect();

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие при чтении данных
     */
    public function onRead(callable $callback);

    /**
     * @param mixed $data
     * @return mixed
     * вызывает событияе при чтении данных
     */
    function _onRead($data);


}