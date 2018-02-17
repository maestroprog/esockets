<?php

use Esockets\Client;

ini_set('log_errors', false);
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/../../vendor/autoload.php';

$configurator = new \Esockets\Base\Configurator(require 'config.php');
$httpServer = $configurator->makeServer();
$httpServer->connect(new \Esockets\Socket\Ipv4Address('0.0.0.0', '8181'));
$fork = pcntl_fork();
cli_set_process_title('PHP Fork ' . $fork);
$fork = pcntl_fork();
cli_set_process_title('PHP Fork ' . $fork);

$fileLoader = new class
{
    private $existsCache = [];
    private $cache;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->cache = [];
    }

    public function existFile(string $path): bool
    {
        if (isset($this->existsCache[$path])) {
            $exists = $this->existsCache[$path];

            if ($exists) {
                return true;
            }
        }
        return $this->existsCache[$path] = file_exists($path);
    }

    public function loadFile(string $path): string
    {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }
        return $this->cache[$path] = file_get_contents($path);
    }
};

$httpServer->onFound(function (Client $client) use ($fileLoader) {
    $client->onReceive(function ($request) use ($client, $fileLoader) {
        if ($request instanceof HttpRequest) {
            $baseDir = __DIR__ . '/www/'; // базовая директория сервера
            // декодируем url закодированную строку URI, обрезаем пробельные символы, и обрезаем слеш в начале
            // а также убираем все небезопасные символы из строки
            $parsed = parse_url(ltrim(trim(rawurldecode($request->getRequestUri())), '/'));
            $uri = $parsed['path'];
            if (empty($uri)) {
                $uri = 'index.html';
            }
            $mime = 'text/html';
            $path = $baseDir . $uri;
            if (!$fileLoader->existFile($path)) {
                $response = new HttpResponse(404, 'Not found', '<h1>Not found</h1><p>' . $uri . '</p>');
            } else {
                $time = microtime(true);
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
//                            $mime = mime_content_type($path);
                    }
                    $body = $fileLoader->loadFile($path);
                }
                $body .= '<div>exec ' . (microtime(true) - $time) . '</div>';
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
$httpServer->unblock();
for (; ;) {
    try {
        $httpServer->find();
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
    }
}

return 0;
