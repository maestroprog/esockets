<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 26.10.16
 * Time: 21:12
 */

namespace maestroprog\esockets\io;


use maestroprog\esockets\base\Net;
use maestroprog\esockets\debug\Log;
use maestroprog\esockets\io\base\Middleware;

class UdpSocket extends Middleware
{

    /**
     * @var Net
     */
    private $connection;

    /**
     * @var resource of socket
     */
    private $socket;

    public function __construct(Net $connection)
    {
        $this->connection = $connection;
        $this->socket = $connection->getConnection();
    }

    public function read(int $length, bool $need = false)
    {
        $buffer = null;
        $ip = null;
        $port = 0;
        if ($bytes = socket_recvfrom($this->socket, $buffer, $length, 0, $ip, $port)) {
            return [$ip, $port, $buffer];
        } elseif ($bytes === 0) {
            Log::log('0 bytes read');
        } else {
            Log::log('reading fail with error ' . socket_last_error($this->socket));
        }
        return false;
    }

    public function send(string &$data)
    {
        list($addr, $port) = $this->connection->getPeerAddress();
        socket_sendto($this->socket, $data, strlen($data), 0, $addr, $port);
    }

}