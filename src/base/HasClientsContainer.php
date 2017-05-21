<?php

namespace Esockets\base;

interface HasClientsContainer
{
    /**
     * @return ClientsContainerInterface
     */
    public function getClientsContainer(): ClientsContainerInterface;
}
