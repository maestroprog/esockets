<?php

namespace Esockets\dummy;

use Esockets\base\AbstractConnectionResource;

class DummyConnectionResource extends AbstractConnectionResource
{
    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return 0;
    }

    public function getResource()
    {
        return null;
    }
}
