<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 20:43
 */

namespace maestroprog\esockets\protocol\base;

use maestroprog\esockets\io\base\Aware as IOAware;

abstract class UseIO implements Aware
{
    /**
     * @var IOAware
     */
    protected $provider;

    /**
     * Здесь мы реализовали необходимый конструктор класса.
     *
     * @inheritdoc
     */
    public function __construct(IOAware $Provider)
    {
        $this->provider = $Provider;
    }
}
