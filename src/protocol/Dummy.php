<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 19:24
 */

namespace maestroprog\esockets\protocol;

use maestroprog\esockets\Protocol;

class Dummy implements Protocol
{
    /**
     * @inheritdoc
     */
    function read(bool $createEvent, bool $need = false): mixed
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritdoc
     */
    function send(mixed $data): bool
    {
        // TODO: Implement send() method.
    }

    /**
     * @inheritdoc
     */
    function pack(mixed $data): string
    {
        return (string)$data;
    }

    /**
     * @inheritdoc
     */
    function unpack(string $raw): mixed
    {
        return $raw;
    }
}