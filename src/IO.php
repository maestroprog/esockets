<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 16.10.2016
 * Time: 22:04
 */

namespace maestroprog\esockets;


abstract class IO
{
    protected $protocol;

    public function setProtocol(Protocol $provider)
    {
        $this->protocol = $provider;
    }

    public function getProtocol(): Protocol
    {
        return $this->protocol;
    }

    abstract public function read();

    abstract public function send();
}