<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 20:43
 */

namespace maestroprog\esockets\protocol\base;

use maestroprog\esockets\io\base\IOAware;

abstract class ProtocolUseIO implements ProtocolAware
{
    /**
     * @var IOAware
     */
    protected $provider;

    public function __construct(IOAware $IOProvider)
    {
        $this->provider = $IOProvider;
    }
}
