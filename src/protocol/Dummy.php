<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 19:24
 */

namespace maestroprog\esockets\protocol;

use maestroprog\esockets\protocol\base\UseIO;

class Dummy extends UseIO
{
    /**
     * @inheritdoc
     */
    function read(bool $need = false)
    {
        // @todo проверить что будет с 0
        return $this->provider->read(0, $need);
    }

    /**
     * @inheritdoc
     */
    function send(&$data): bool
    {
        return $this->provider->send($data);
    }
}
