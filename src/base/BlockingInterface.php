<?php

namespace Esockets\base;

interface BlockingInterface
{
    public function block();

    public function unblock();
}
