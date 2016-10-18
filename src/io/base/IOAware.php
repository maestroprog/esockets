<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 21:24
 */

namespace maestroprog\esockets\io\base;

interface IOAware
{
    /**
     * Функция, предоставляющая доступ к IO для чтения.
     * Должна использоваться протокол провайдером.
     *
     * @see ProtocolAware::read()
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
     * @see ProtocolAware::send()
     * @param string $data
     * @return bool
     */
    public function send(string &$data);
}
