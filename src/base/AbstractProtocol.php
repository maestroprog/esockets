<?php

namespace Esockets\base;

abstract class AbstractProtocol implements ReaderInterface, SenderInterface
{
    /**
     * @var IoAwareInterface
     */
    protected $provider;
    protected $eventReceive;

    /**
     * Здесь мы реализовали необходимый конструктор класса.
     *
     * @inheritdoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        $this->provider = $provider;
        $this->eventReceive = new Event();
    }

    public function read(): bool
    {
        for ($i = 0; null !== ($data = $this->returnRead()); $i = 1) {
            $this->eventReceive->call($data);
        }

        return $i > 0;
    }
}
