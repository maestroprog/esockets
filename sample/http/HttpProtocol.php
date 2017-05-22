<?php

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEvent;

final class HttpProtocol extends AbstractProtocol
{
    /**
     * @inheritdoc
     */
    public function returnRead()
    {
        $buffer = '';
        $read = false;
        $data = $this->provider->read($this->provider->getMaxPacketSize(), false);
        /*while (!is_null()) {
            $buffer .= $data;
            $read = true;
        }*/
        if (!is_null($data)) {
            $buffer .= $data;
            $read = true;
        }
        if ($read) {
            list($headers, $other) = explode("\r\n\r\n", $buffer, 2);
            $headers = explode("\r\n", $headers);
            $i = 0;
            $requestUri = '/';
            $parsedHeaders = [];
            foreach ($headers as $header) {
                if ($i === 0) {
                    list($method, $requestUri, $version) = explode(' ', $header, 3);
                } else {
                    list($header, $value) = explode(':', $header, 2);
                    $parsedHeaders[$header] = $value;
                }
                $i++;
            }
            return new HttpRequest($requestUri);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function onReceive(callable $callback): CallbackEvent
    {
        return $this->eventReceive->addEvent(CallbackEvent::create($callback));
    }

    public function send($data): bool
    {
        if ($data instanceof HttpResponse) {
            $data = implode("\r\n", [
                sprintf('HTTP/1.0 %d %s', $data->getCode(), $data->getStatus()),
                'Content-Type: text/html; charset=utf-8',
                sprintf('Content-Length: %d', strlen($data->getBody())),
                'Connection: close',
                '',
                $data->getBody(),
                ''
            ]);
        }
        return $this->provider->send($data);
    }
}
