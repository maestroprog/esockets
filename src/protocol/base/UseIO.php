<?php

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
    public function __construct(IoAwareInterface $provider)
    {
        $this->provider = $provider;
    }
}