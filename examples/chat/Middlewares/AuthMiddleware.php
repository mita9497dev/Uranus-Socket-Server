<?php

namespace Mita\UranusSocketServer\Examples\Chat\Middlewares;

use Mita\UranusSocketServer\Middlewares\MiddlewareInterface;
use Mita\UranusSocketServer\Packets\PacketInterface;
use Ratchet\ConnectionInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(ConnectionInterface $conn, PacketInterface $msg, callable $next)
    {
        $next($conn, $msg);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        return true;
    }
}
