<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 31.05.2016
 * Time: 21:26
 */

namespace Esockets;


class TestEsockets extends \PHPUnit_Framework_TestCase
{
    /**
     * @var $server \Esockets\Server
     * @var $client \Esockets\Client
     */
    private $server, $client;

    public function testServerOpen()
    {
        $this->server = new \Esockets\Server();
        $this->assertTrue($this->server->connect(), 'Сервер не создаётся');
    }

    public function testClientConnect()
    {
        $this->client = new \Esockets\Client();
        $this->assertTrue($this->client->connect(), 'Клиент не может соединиться');
    }

    public function testServerAcceptClient()
    {
        $this->server->onConnectPeer(function (\Esockets\Peer $peer) {

        });

        $this->server->listen();
    }

    public function testClientSendData()
    {
        $this->assertTrue($this->client->send('Hello world'), 'Клиент не может отправить данные');
    }
}
