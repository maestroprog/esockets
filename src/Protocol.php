<?php

/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 19:24
 */
namespace maestroprog\esockets;


interface Protocol
{
    /**
     * Функция пробует прочитать данные из сокета.
     * Параметр $need = true принуждает прочитать данные из сокета.
     *      Если прочитать ничего не удалось с определенным тай-аутом,
     *      то считаем, что соединение разорвано.
     * Параметр $createEvent = true говорит о том, что после чтения данных
     *      нужно создать событие, и передать туда прочтенные данные.
     *      При $createEvent = false прочтенные данные нужно вернуть из функции.
     *
     * Функция может возвращать что угодно, но:
     *      Если функция вернула false, то мы считаем, что соединение было разорвано.
     *      Если функция вернула true, то мы считаем, что соединение в порядке.
     *      Если функция вернула null в случае $need = false - значит она ничего не прочитала.
     *
     * @param bool $createEvent
     * @param bool $need
     * @return mixed
     */
    function read(bool $createEvent, bool $need = false): mixed;

    /**
     * Функция отправляет данные через сокет.
     *
     * @param mixed $data
     * @return bool
     */
    function send(mixed $data): bool;

    /**
     * Функция пакует данные для передачи их через сокет.
     *
     * @param mixed $data
     * @return string
     */
    function pack(mixed $data): string;

    /**
     * Функция распаковывает принятые через сокет данные.
     *
     * @param string $raw
     * @return mixed
     */
    function unpack(string $raw): mixed;
}