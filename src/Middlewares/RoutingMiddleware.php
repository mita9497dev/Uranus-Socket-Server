<?php
namespace Mita\UranusSocketServer\Middlewares;

use FTP\Connection;
use Mita\UranusSocketServer\Controllers\ControllerInterface;
use Mita\UranusSocketServer\Exceptions\RoutingException;
use Mita\UranusSocketServer\Packets\PacketInterface;
use Psr\Container\ContainerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class RoutingMiddleware implements MiddlewareInterface
{
    protected $router;
    protected $matcher;
    protected $container;

    public function __construct(RouterInterface $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->matcher = new UrlMatcher($router->getRouteCollection(), new RequestContext());
        $this->container = $container;
    }

    public function handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)
    {
        try {
            $route = $packet->getRoute();

            $parameters = $this->matcher->match($route);
            if (!$parameters || !isset($parameters['_controller'])) {
                throw new RoutingException("Invalid route: $route");
            }

            $next($conn, $packet, $parameters);

        } catch (\Exception $e) {
            throw new RoutingException("Routing error: " . $e->getMessage(), 0, $e);
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        return true;
    }
}
