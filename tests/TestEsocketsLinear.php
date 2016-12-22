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
    private static $peer_accepted = 0;

    public function provider()
    {
        static $data = null;
        if (is_null($data)) {
            $data = [[new TcpServer(), new TcpClient()]];
        }
        return $data;
    }

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     */
    public function testServerOpen(TcpServer $server, TcpClient $client)
    {
        $this->assertTrue($server->connect(), 'Сервер не создаётся');
    }

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     * @depends      testServerOpen
     */
    public function testClientConnect(TcpServer $server, TcpClient $client)
    {
        $this->assertTrue($client->connect(), 'Клиент не может соединиться');
    }

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     * @depends      testClientConnect
     */
    public function testServerAcceptClient(TcpServer $server, TcpClient $client)
    {
        $server->onConnectPeer(function (Peer $peer) {
            self::$peer_accepted++;

            $peer->onRead([$this, 'serverPeerReceiveData']);
        });
        $server->listen();
    }

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     * @depends      testServerAcceptClient
     */
    public function testServerAcceptPeer(TcpServer $server, TcpClient $client)
    {
        $this->assertTrue(self::$peer_accepted > 0);
    }

    protected static $client_send_msg = 'Hello world';

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     * @depends      testServerAcceptPeer
     */
    public function testClientSendData(TcpServer $server, TcpClient $client)
    {
        $this->assertTrue($client->send(self::$client_send_msg), 'Клиент не может отправить данные');
    }

    protected static $peer_read_msg = false;

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     * @depends      testClientSendData
     */
    public function testServerReceiveData(TcpServer $server, TcpClient $client)
    {
        $server->select();
        $server->read();

        $this->assertNotFalse(self::$peer_read_msg, 'А сервер ничего не получил!');
        $this->assertEquals(self::$client_send_msg, self::$peer_read_msg, 'Прочитали какую-то хрень');
    }

    public function serverPeerReceiveData($msg)
    {
        self::$peer_read_msg = $msg;
    }

    /**
     * @param $server TcpServer
     * @param $client TcpClient
     * @dataProvider provider
     * @depends      testServerReceiveData
     */
    public function testClientDisconnect(TcpServer $server, TcpClient $client)
    {
        $client->disconnect();
        $this->assertFalse($client->is_connected());
    }
}
