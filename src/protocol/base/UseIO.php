<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 18.10.16
 * Time: 20:43
 */

namespace Esockets\protocol\base;

use Esockets\io\base\IoAwareInterface;

abstract class UseIO implements AwareInterface
{
    /**
     * @var IoAwareInterface
     */
    protected $provider;

    /**
     * Здесь мы реализовали необходимый конструктор класса.
     *
     * @inheritdoc
     */
    public function __construct(IoAwareInterface $Provider)
    {
        $this->provider = $Provider;
    }
}
