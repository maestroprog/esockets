<?php

namespace Esockets\base;

abstract class AbstractConnectionResource
{
    protected $resource;

    abstract public function getResource();
}
