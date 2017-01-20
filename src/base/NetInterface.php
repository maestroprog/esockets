<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 13:04
 */

namespace Esockets\base;

interface NetInterface
{
    /**
     * Открывает соединение.
     * Возвращает true при успешном открытии, false при сбое.
     *
     * @return bool
     */
    public function connect();

    /**
     * Читает поступившие данные из сети.
     * Дожидается поступления данных, если необходимо.
     *
     * @param bool $need
     */
    public function read(bool $need = false);

    /**
     * Назначает событие при чтении данных
     *
     * @param callable $callback
     */
    public function onRead(callable $callback);

    /**
     * Отправляет пакет данных.
     * Возвращает количество успешно отправленных байтов.
     *
     * @param $data
     * @return int
     */
    public function send($data);

    /**
     * Функция, обеспечивающая жизнь сокету.
     * Что делает:
     * - контролирует текущее состояние соединения,
     * - проверяет связь с заданным интервалом,
     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи.
     * Возвращает true, если сокет жив, false если не работает.
     * Можно использовать в бесконечном цикле:
     * while (Net->live()) {
     *  Net->send(data);
     * }
     *
     * @return bool
     */
    function live();

    /**
     * Закрывает соединение.
     */
    public function disconnect();

    /**
     * Назначает событие при отсоединении.
     *
     * @param callable $callback
     */
    public function onDisconnect(callable $callback);
}
