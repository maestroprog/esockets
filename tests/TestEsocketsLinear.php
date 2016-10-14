<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 31.05.2016
 * Time: 21:26
 */

namespace Esockets;

use TestEnvironment as Env;


class TestEsockets extends \PHPUnit_Framework_TestCase
{
    private static $peer_accepted = 0;

    public function testServerOpen()
    {
        Env::$server = new Server();
        $this->assertTrue(Env::$server->connect(), 'Сервер не создаётся');
    }

    public function testClientConnect()
    {
        Env::$client = new Client();
        $this->assertTrue(Env::$client->connect(), 'Клиент не может соединиться');
    }

    public function testServerAcceptClient()
    {
        Env::$server->onConnectPeer(function (Peer $peer) {
            self::$peer_accepted++;

            $peer->onRead([$this, 'serverPeerReceiveData']);
        });
        Env::$server->listen();
    }

    public function testServerAcceptPeer()
    {
        $this->assertTrue(self::$peer_accepted > 0);
    }

    protected static $client_send_msg = 'Hello world';

    public function testClientSendData()
    {
        $this->assertTrue(Env::$client->send(self::$client_send_msg), 'Клиент не может отправить данные');
    }

    protected static $peer_read_msg = false;

    public function testServerReceiveData()
    {
        Env::$server->select();
        Env::$server->read();

        $this->assertNotFalse(self::$peer_read_msg, 'А сервер ничего не получил!');
        $this->assertEquals(self::$client_send_msg, self::$peer_read_msg, 'Прочитали какую-то хрень');
    }

    public function serverPeerReceiveData($msg)
    {
        self::$peer_read_msg = $msg;
    }

    public function testClientDisconnect()
    {
        Env::$client->disconnect();
        $this->assertFalse(Env::$client->is_connected());
    }
}
