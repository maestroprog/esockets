<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 15.10.2016
 * Time: 19:24
 */

namespace maestroprog\esockets\protocol;

use maestroprog\esockets\protocol\base\ProtocolUseIO;

class Dummy extends ProtocolUseIO
{
    /**
     * @inheritdoc
     */
    function read(bool $need = false): mixed
    {
        // @todo проверить что будет с 0
        return $this->provider->read(0, $need);
    }

    /**
     * @inheritdoc
     */
    function send(mixed &$data): bool
    {
        return $this->provider->send($data);
    }
}
