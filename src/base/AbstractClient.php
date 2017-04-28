<?php

namespace Esockets\base;


abstract class AbstractClient implements
    ConnectorInterface,
    ConnectionSupportInterface,
    ReaderInterface,
    SenderInterface,
    PingInterface
{

}