<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 19:24
 */

namespace maestroprog\esockets\protocol\base;

use maestroprog\esockets\io\base\Aware as IOAware;

/**
 * Интерфейс, показывающий возможности обмена информацией между клиентами сети.
 */
interface Aware
{
    /**
     * Для создания класса нам потребуется поставщик ввода/вывода.
     *
     * Aware constructor.
     * @param IOAware $Provider
     */
    public function __construct(IOAware $Provider);

    /**
     * Функция пробует прочитать данные из сокета.
     * Параметр $need = true принуждает прочитать данные из сокета.
     *      Если прочитать ничего не удалось с определенным тай-аутом,
     *      то считаем, что соединение разорвано.
     * Функция может возвращать что угодно, но:
     *      Если функция вернула false, то мы считаем, что соединение было разорвано.
     *      Если функция вернула true, то мы считаем, что соединение в порядке.
     *      Если функция вернула null в случае $need = false - значит она ничего не прочитала.
     *
     * @param bool $need
     * @return mixed
     */
    function read(bool $need = false);

    /**
     * Функция отправляет данные через сокет.
     * Возвращает true при успехе, иначе false.
     *
     * @param mixed $data
     * @return bool
     */
    function send(&$data): bool;
}
