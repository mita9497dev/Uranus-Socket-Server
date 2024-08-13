<?php

namespace Mita\UranusSocketServer\Middlewares;

use Ratchet\ConnectionInterface;

interface MiddlewareInterface
{
    /**
     * Handle an incoming WebSocket message.
     *
     * @param ConnectionInterface $conn
     * @param string $msg
     * @param callable $next
     * @return mixed
     */
    public function handle(ConnectionInterface $conn, $msg, callable $next);
}
