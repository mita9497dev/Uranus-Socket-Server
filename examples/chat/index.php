<?php 

require __DIR__ . '/../../vendor/autoload.php';

use Mita\UranusSocketServer\SocketServer;

$settings = [
    'host' => '127.0.0.1',
    'port' => 7654,
    'router_path' => __DIR__ . '/routes.yaml'
];

$userDiConfig = require __DIR__ . '/user_di_config.php';

$socketServer = new SocketServer($settings, $userDiConfig);

$socketServer->registerEventListener('connection.added', function ($conn) {
    echo "New connection added: " . $conn->resourceId . "\n";
});

$socketServer->run();