<?php

namespace Esockets\Dummy;

use Esockets\Base\AbstractAddress;

final class DummyAddress extends AbstractAddress
{
    public function __toString(): string
    {
        return 'dummy';
    }
}
