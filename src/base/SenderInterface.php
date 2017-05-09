<?php

namespace Esockets\base;

interface SenderInterface
{
    public function send($data): bool;
}
