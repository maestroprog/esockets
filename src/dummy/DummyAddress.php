<?php

namespace Esockets\dummy;

use Esockets\base\AbstractAddress;

final class DummyAddress extends AbstractAddress
{
    public function __toString(): string
    {
        return 'dummy';
    }
}
