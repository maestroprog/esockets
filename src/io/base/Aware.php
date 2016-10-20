<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 21:24
 */

namespace maestroprog\esockets\io\base;

/**
 * Интерфейс, показывающий возможности ввода/вывода.
 * Для обеспечения ввода-вывода необходимо его реализовать.
 * Реализованный протокол использует данный интерфейс для омбена информацией.
 */
interface Aware
{
    /**
     * Функция, предоставляющая доступ к IO для чтения.
     * Должна использоваться протокол провайдером.
     *
     * @see Aware::read()
     * @param int $length
     * @param bool $need
     * @return mixed
     */
    public function read(int $length, bool $need = false);

    /**
     * Функция, предоставляющая доступ к IO для записи.
     *      Должна использоваться протокол провайдером.
     * Параметр $data - только стррока с данными для передачи.
     *      Берём по ссылке чтобы лишний раз не копировать.
     * Возвращает true в случае успеха, иначе false.
     *
     * @see Aware::send()
     * @param string $data
     * @return bool
     */
    public function send(string &$data);
}