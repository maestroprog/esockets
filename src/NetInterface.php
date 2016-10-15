<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 13:04
 */

namespace maestroprog\esockets;


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
     * вовзаращает true при успешном соединении, false при сбое
     */
    public function connect();

    /**
     * отсоединяет от сети
     */
    public function disconnect();

    /**
     * читает поступившие данные из сети
     * послушный метод, ничего не возвращает,
     * при чтении вызывает обработчик события, назначенное в onRead()
     */
    public function read();

    /**
     * @return bool
     * отправляет пакет данных в сеть
     * возвращает true при успешной отправке, false при сбое
     */
    public function send($data);

    /**
     * @return bool
     * функция, обеспечивающая жизнь сокету
     * что делает:
     * - контролирует текущее состояние соединения
     * - проверяет связь с заданным интервалом
     * - выполняет чтение входящих данных
     * - выполняет переподключение при обрыве связи
     * возвращает true, если сокет жив, false если не работает
     * можно использовать в бесконечном цикле
     * while (Net->live()) {
     *  Net->send(data);
     * }
     */
    function live();

    /**
     * @param callable $callback
     * назначает событие при отсоединении
     */
    public function onDisconnect(callable $callback);

    /**
     * @param callable $callback
     * назначает событие при чтении данных
     */
    public function onRead(callable $callback);

}