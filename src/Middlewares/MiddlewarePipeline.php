<?php 

namespace Mita\UranusSocketServer\Middlewares;

use Mita\UranusSocketServer\Packets\PacketInterface;
use Ratchet\ConnectionInterface;

class MiddlewarePipeline
{
    protected $middlewares = [];
    protected $index = 0;

    public function add(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function process(ConnectionInterface $conn, PacketInterface $packet, callable $finalHandler)
    {
        $this->index = 0;
        $this->next($conn, $packet, $finalHandler);
    }

    protected function next(ConnectionInterface $conn, PacketInterface $packet, callable $finalHandler)
    {
        if (isset($this->middlewares[$this->index])) {
            $middleware = $this->middlewares[$this->index];
            $this->index++;

            try {
                $middleware->handle($conn, $packet, function($conn, $packet) use ($finalHandler) {
                    $this->next($conn, $packet, $finalHandler);
                });
                
            } catch (\Exception $e) {
                echo "Middleware error: " . $e->getMessage() . "\n";
                
                $this->next($conn, $packet, $finalHandler);
            }
        } else {
            $finalHandler($conn, $packet);
        }
    }
}
