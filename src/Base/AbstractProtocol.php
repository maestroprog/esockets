<?php

namespace Esockets\Base;

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

    public static function create(IoAwareInterface $provider): self
    {
        return new static($provider);
    }

    public function read(): bool
    {
        try {
            if (null !== ($data = $this->returnRead())) {
                $this->eventReceive->call($data);
            }

            return true;
        } catch (Exception\ReadException $e) {
            return false;
        }
    }
}
