<?php

namespace Esockets\Debug;

use Esockets\Base\AbstractProtocol;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\IoAwareInterface;
use Esockets\Base\PingPacket;
use Esockets\Base\PingSupportInterface;
use Esockets\Protocol\EasyStream;

class LoggingProtocol extends AbstractProtocol implements PingSupportInterface
{
    private static $realProtocolClass = EasyStream::class;
    /**
     * @var EasyStream|\Esockets\Protocol\EasyDataGram|AbstractProtocol
     */
    private $realProtocol;
    private $pingCallback;

    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);
        $this->realProtocol = new self::$realProtocolClass($provider);
        $this->eventReceive->attachCallbackListener(function ($data) {
            $this->log('READING', $data);
        });
        $this->realProtocol->onPingReceived(function (PingPacket $ping) {
            $this->log('PING RECEIVED', $ping->getValue() . ' ' . ($ping->isResponse() ? 'pong' : 'ping'));
            $this->pingReceived($ping);
        });
    }

    protected function log($type, $data)
    {
        $data = substr(json_encode($data, JSON_UNESCAPED_UNICODE), 0, 64);
        Log::log($type, $data);
    }

    private function pingReceived(PingPacket $ping): void
    {
        if (null !== $this->pingCallback) {
            call_user_func($this->pingCallback, $ping);
        }
    }

    public static function withRealProtocolClass(string $class): string
    {
        self::$realProtocolClass = $class;
        return static::class;
    }

    public function returnRead()
    {
        $data = $this->realProtocol->returnRead();
        if ($data !== null) {
            $this->log('FORCED READING', $data);
        }
        return $data;
    }

    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    public function send($data): bool
    {
        $this->log('SENDING', $data);
        return $this->realProtocol->send($data);
    }

    public function ping(PingPacket $pingPacket): void
    {
        $this->logPingPong($pingPacket);
        $this->realProtocol->ping($pingPacket);
    }

    /**
     * @param PingPacket $pingPacket
     */
    private function logPingPong(PingPacket $pingPacket): void
    {
        $this->log('PING', $pingPacket->getValue() . ' ' . ($pingPacket->isResponse() ? 'pong' : 'ping'));
    }

    public function pong(callable $pongReceived): void
    {
        $this->realProtocol->pong(function (PingPacket $packet) use ($pongReceived) {
            $this->logPingPong($packet);
            $pongReceived($packet);
        });
    }

    /**
     * @inheritdoc
     */
    public function onPingReceived(callable $pingReceived): void
    {
        $this->pingCallback = $pingReceived;
    }
}
