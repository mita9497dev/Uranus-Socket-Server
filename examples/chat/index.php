<?php 

require __DIR__ . '/../../vendor/autoload.php';

use Mita\UranusSocketServer\SocketServer;

$settings = [
    'host' => '127.0.0.1',
    'port' => 7654,
    'router_path' => __DIR__ . '/routes.yaml'
];

$socketServer = new SocketServer($settings);
$socketServer->run();