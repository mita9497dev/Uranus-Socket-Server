<?php 
namespace Mita\UranusSocketServer\Services;

use Mita\UranusSocketServer\Exceptions\RoutingException;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Mita\UranusSocketServer\Middlewares\RoutingMiddleware;
use Psr\Container\ContainerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class WebSocketService implements MessageComponentInterface {
    protected $connections;
    protected $router;
    protected $matcher;
    protected $authMiddleware;
    protected $container;

    public function __construct(ConnectionManager $connections, RouterInterface $router, RoutingMiddleware $authMiddleware, ContainerInterface $container) {
        $this->connections = $connections;
        $this->authMiddleware = $authMiddleware;
        $this->router = $router;
        $this->matcher = new UrlMatcher($router->getRouteCollection(), new RequestContext());
        $this->container = $container;
    }

    public function onOpen(ConnectionInterface $conn) {
        echo "New connection from {$conn->resourceId}\n";

        if (!$this->authMiddleware->onOpen($conn)) {
            echo "Connection {$conn->resourceId} closed due to insufficient permissions\n";
            $conn->close();
            return;
        }
    
        try {
            $this->connections->add($conn);
            
        } catch (\Exception $e) {
            echo "Routing error: " . $e->getMessage() . "\n";
            if ($this->connections->get($conn->resourceId)) {
                $this->connections->remove($conn);
            }
            $conn->close();
        }
    }

    /** 
     * Message handler
     * 
     * @param ConnectionInterface $from
     * @param string $msg
     * 
     * @throws RoutingException
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->authMiddleware->handle($from, $msg, function($conn, $route, $message, $parameters) {
            [$controller, $action] = explode('::', $parameters['_controller']);
            $controller = $this->container->get($controller);

            if (method_exists($controller, $action)) {
                call_user_func_array([$controller, $action], [$conn, $message, $parameters]);
            
            } else {
                throw new RoutingException("Method $action not found in controller $controller");
            }
        });
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Connection {$conn->resourceId} closed\n";
        $this->connections->remove($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error on connection {$conn->resourceId}: " . $e->getMessage() . "\n";
        $conn->close();
    }
}
