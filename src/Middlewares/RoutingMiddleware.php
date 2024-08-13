<?php
namespace Mita\UranusSocketServer\Middlewares;

use Mita\UranusSocketServer\Exceptions\RoutingException;
use Psr\Container\ContainerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class RoutingMiddleware
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

    public function handle(ConnectionInterface $conn, $msg, callable $next)
    {
        $data = json_decode($msg, true);
        if (!array_key_exists('route', $data) || !array_key_exists('msg', $data)) {
            throw new RoutingException("Invalid message format");
        }

        $route = $data['route'];
        $message = $data['msg'];

        try {
            $parameters = $this->matcher->match($route);
            if (!$parameters || !isset($parameters['_route'])) {
                throw new RoutingException("Invalid route: $route");
            }

            // Lấy middleware từ `defaults` trong route
            if (isset($parameters['_middleware'])) {
                foreach ($parameters['_middleware'] as $middlewareClass) {
                    if (class_exists($middlewareClass)) {
                        $middleware = $this->container->get($middlewareClass);

                        if ($middleware instanceof MiddlewareInterface) {
                            $middleware->handle($conn, $msg, function ($conn, $msg) use ($next, $parameters) {
                                $next($conn, $parameters['route'], $msg, $parameters);
                            });
                        } else {
                            throw new RoutingException("Middleware $middlewareClass must implement MiddlewareInterface");
                        }
                    } else {
                        throw new RoutingException("Middleware class $middlewareClass does not exist");
                    }
                }
            } else {
                $next($conn, $route, $message, $parameters);
            }
        } catch (\Throwable $e) {
            throw new RoutingException("Routing error: " . $e->getMessage(), 0, $e);
        }

        return $next($conn, $route, $message, $parameters);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        return true;
    }
}
