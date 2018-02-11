<?php

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEventListener;

final class HttpProtocol extends AbstractProtocol
{
    /**
     * @inheritdoc
     */
    public function returnRead()
    {
        $buffer = '';
        $read = false;
        $data = $this->provider->read($this->provider->getReadBufferSize(), false);
        if (null !== $data) {
            $buffer .= $data;
            $read = true;
        }
        if ($read) {
            $headers = explode("\r\n\r\n", $buffer, 2)[0];
            $headers = explode("\r\n", $headers);
            $i = 0;
            $requestUri = '/';
            $parsedHeaders = [];
            foreach ($headers as $header) {
                if ($i === 0) {
                    $matches = [];
                    $uri = null;
                    if (preg_match('/^([^\s]{1,})\s{1,}([^\s]{1,})\s{1,}([^\s]{1,})$/', trim($header), $matches)) {
                        list($header, $method, $uri, $version) = $matches;
                    }
                    if ($uri) {
                        $requestUri = $uri;
                    }
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
    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    public function send($data): bool
    {
        if ($data instanceof HttpResponse) {
            $data = $data->getHeaders() . "\r\n" . "\r\n" . $data->getBody();
        }
        return $this->provider->send($data);
    }
}
