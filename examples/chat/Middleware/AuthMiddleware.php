<?php

namespace Examples\Middleware;

use Mita\UranusSocketServer\Middlewares\MiddlewareInterface;
use Ratchet\ConnectionInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(ConnectionInterface $conn, $msg, callable $next)
    {
        // Giả sử xác thực đơn giản bằng một token cố định
        $data = json_decode($msg, true);
        if (!isset($data['token']) || $data['token'] !== 'valid_token') {
            $conn->send("Unauthorized");
            return;
        }

        // Nếu xác thực thành công, tiếp tục xử lý
        $next($conn, $msg);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Thực hiện xử lý khi kết nối mở (nếu cần)
        return true;
    }
}
