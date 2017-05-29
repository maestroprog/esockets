<?php

use Esockets\Client;

ini_set('log_errors', false);
ini_set('display_errors', true);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';

$configurator = new \Esockets\base\Configurator(require 'config.php');
$httpServer = $configurator->makeServer();
$httpServer->connect(new \Esockets\socket\Ipv4Address('0.0.0.0', '81'));
$httpServer->onFound(function (Client $client) {
    $client->onReceive(function ($request) use ($client) {
        if ($request instanceof HttpRequest) {
            $baseDir = __DIR__ . '/www/'; // базовая директория сервера
            // декодируем url закодированную строку URI, обрезаем пробельные символы, и обрезаем слеш в начале
            // а также убираем все небезопасные символы из строки
            $uri = preg_replace(
                '/[^\w\-\/\.]/',
                '',
                ltrim(trim(rawurldecode($request->getRequestUri())), '/')
            );
            if (empty($uri)) {
                $uri = 'index.html';
            }
            $mime = 'text/html';
            $path = $baseDir . $uri;
            if (!file_exists($path)) {
                $response = new HttpResponse(404, 'Not found', '<h1>Not found</h1><p>' . $uri . '</p>');
            } else {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                if ($extension === 'php') {
                    ob_start();
                    try {
                        include $path;
                    } catch (\Throwable $e) {
                        echo '<p>Error: ' . $e->getMessage() . '</p>';
                    } finally {
                        $body = ob_get_contents();
                        ob_end_clean();
                    }
                } else {
                    switch ($extension) {
                        case 'css':
                            $mime = 'text/css';
                            break;
                        default:
                            $mime = mime_content_type($path);
                    }
                    $body = file_get_contents($path);
                }
                $response = new HttpResponse(
                    200,
                    'OK',
                    $body,
                    ['Content-Type: ' . $mime . '; charset=utf-8']
                );
            }
        } else {
            $response = new HttpResponse(400, 'Bad request', '<h1>Bad request</h1>');
        }
        $client->send($response);
        $client->disconnect();
    });
});

for (; ;) {
    try {
        $httpServer->find();
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
    }
}

return 0;
