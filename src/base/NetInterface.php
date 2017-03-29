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
     * Закрывает соединение.
     *
     * @return void
     */
    public function disconnect();

    /**
     * Назначает обработчик события отсоединения.
     *
     * @param callable $callback
     * @return void
     */
    public function onDisconnect(callable $callback);

    /**
     * Читает поступившие данные из сети.
     * Дожидается поступления данных, если необходимо.
     *
     * @param bool $need
     * @return void
     */
    public function read(bool $need = false);

    /**
     * Назначает обработчик события поступления данных для чтения.
     *
     * @param callable $callback
     * @return void
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
     * Поддерживает жизнь соединения.
     * Что делает:
     * - контролирует текущее состояние соединения,
     * - проверяет связь с заданным интервалом,
     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи, если это включено,
     *
     * Возвращает true, если сокет жив, false если не работает.
     * Можно использовать в бесконечном цикле:
     * while ($NET->live()) {
     *     // тут делаем что-то.
     * }
     *
     * @return bool
     */
    public function live();
}
