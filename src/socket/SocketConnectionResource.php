<?php

namespace Esockets\socket;

use Esockets\base\AbstractConnectionResource;
use Esockets\base\exception\ConnectionException;

final class SocketConnectionResource extends AbstractConnectionResource
{
    protected $socket;
    protected $peerAddress;

    /**
     * @param $socket
     * @throws ConnectionException
     */
    public function __construct($socket)
    {
        if (!is_resource($socket)) {
            throw new ConnectionException('$socket don\'t is resource');
        } elseif (get_resource_type($socket) !== 'Socket') {
            throw new ConnectionException('Unknown resource type: ' . get_resource_type($socket));
        }
        $this->socket = $socket;
    }

    /**
     * @return resource Socket resource
     */
    public function getResource()
    {
        return $this->socket;
    }
}
