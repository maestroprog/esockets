<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 23.03.2017
 * Time: 0:38
 */

namespace Esockets\base;


interface TransportInterface extends ReaderInterface, SenderInterface
{
    public function ping();

    public function pong(PingPacket $pingData);
}