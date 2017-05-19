<?php

namespace Esockets\base;

abstract class AbstractProtocol implements ReaderInterface, SenderInterface
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
