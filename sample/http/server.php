<?php

use Esockets\Client;

ini_set('log_errors', false);
ini_set('display_errors', true);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';

$configurator = new \Esockets\base\Configurator(require 'config.php');
$httpServer = $configurator->makeServer();
$httpServer->connect(new \Esockets\socket\Ipv4Address('127.0.0.1', '81'));
$httpServer->onFound(function (Client $client) {
//    \Esockets\debug\Log::log('i found ' . $client->getPeerAddress());
    $client->onReceive(function (HttpRequest $request) use ($client) {
//        \Esockets\debug\Log::log('i received ' . $client->getPeerAddress());

        $client->send(new HttpResponse(
            200,
            'OK',
            '<p>Esockets HTTP server sample v 2.0</p>'
            . '<p>You requested a uri: ' . $request->getRequestUri() . '</p>'
        ));
        $client->disconnect();
    });
});

while (true) {
    $httpServer->find();
//    usleep(1000);
}

return 0;
