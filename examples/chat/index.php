<?php 

require __DIR__ . '/../../vendor/autoload.php';

use Mita\UranusSocketServer\SocketServer;

// Thiết lập cài đặt
$settings = [
    'host' => '127.0.0.1',
    'port' => 7654,
    'router_path' => __DIR__ . '/routes.yaml'
];
// Nạp cấu hình DI của người dùng
$userDiConfig = require __DIR__ . '/user_di_config.php';

// Khởi động server
$socketServer = new SocketServer($settings);
$socketServer->run();
