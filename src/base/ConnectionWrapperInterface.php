<?php

namespace Esockets\base;

interface ConnectionWrapperInterface extends ReaderInterface, SenderInterface
{
    public function __construct(ConnectorInterface $connection);
}
