<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 28.03.2017
 * Time: 18:21
 */

namespace Esockets\base;


interface BroadcastingInterface
{
    public function sendToAll($data);
}