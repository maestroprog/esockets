<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 22.03.2017
 * Time: 19:40
 */

namespace Esockets\base;


interface SenderInterface
{
    public function send($data): bool;


    public function getTransmittedBytesCount(): int;

    public function getTransmittedPacketCount(): int;
}