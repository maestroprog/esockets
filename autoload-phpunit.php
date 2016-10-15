<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 14.10.2016
 * Time: 23:20
 */
require 'autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('log_errors', true);
ini_set('error_log', 'phpunit.log');
if (file_exists('phpunit.log')) unlink('phpunit.log');

/**
 * Класс тестового окружения.
 * Имеет по одному экземпляру сервера, клиента, и пира.
 */
class TestEnvironment
{
    /**
     * @var \maestroprog\esockets\Server
     */
    public static $server;

    /**
     * @var \maestroprog\esockets\Client
     */
    public static $client;

    /**
     * @var \maestroprog\esockets\Peer
     */
    public static $peer;
}