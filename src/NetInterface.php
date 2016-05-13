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
    const DATA_RAW = 0;
    const DATA_JSON = 1;
    const DATA_INT = 2;
    const DATA_FLOAT = 4;
    const DATA_STRING = 8;
    const DATA_ARRAY = 16;
    const DATA_EXTENDED = 32; // reserved for objects
    const DATA_PING_PONG = 64; // reserved
    const DATA_CONTROL = 128;

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
     * @return mixed
     * функция, обеспечивающая жизнь сокету
     * что делает:
     * - контролирует текущее состояние соединения
     * - проверяет связь через установленные промежутки времени
     * - выполняет переподключение на установленные промежутки времени при тайм-ауте проверки связи
     */
    function live();

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие при отсоединении
     */
    public function onDisconnect(callable $callback);

    /**
     * @param callable $callback
     * @return mixed
     * назначает событие при чтении данных
     */
    public function onRead(callable $callback);

}