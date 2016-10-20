<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 13:04
 */

namespace maestroprog\esockets\base;


interface NetInterface
{

    /**
     * @return bool
     * соединяет с сетью
     * создаёт поставщика ввода вывода
     * вовзаращает true при успешном соединении, false при сбое
     */
    public function connect();

    /**
     * отсоединяет от сети
     */
    public function disconnect();

    /**
     * Читает поступившие данные из сети
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