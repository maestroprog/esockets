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
    /** @var Server */
    static private $server;
    /** @var Client */
    static private $client;
    /** @var int */
    static private $peer_accepted = 0;

    public function testServerOpen()
    {
        self::$server = new Server();
        $this->assertTrue(self::$server->connect(), 'Сервер не создаётся');
    }

    public function testClientConnect()
    {
        self::$client = new Client();
        $this->assertTrue(self::$client->connect(), 'Клиент не может соединиться');
    }

    public function testServerAcceptClient()
    {
        self::$server->onConnectPeer(function (Peer $peer) {
            self::$peer_accepted++;
        });
        self::$server->listen();
    }

    public function testServerAcceptPeer()
    {
        $this->assertTrue(self::$peer_accepted > 0);
    }

    public function testClientSendData()
    {
        $this->assertTrue(self::$client->send('Hello world'), 'Клиент не может отправить данные');
    }

    public function testServerReceiveData()
    {
        //self::$server->
    }

    public function testClientDisconnect()
    {
        self::$client->disconnect();
        self::assertTrue(self::$client->is_connected());
    }
}
