<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 20:43
 */

namespace Esockets\protocol\base;

use Esockets\io\base\Aware as IOAware;

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
