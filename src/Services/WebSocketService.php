<?php 
namespace Mita\UranusSocketServer\Services;

use Mita\UranusSocketServer\Controllers\ControllerInterface;
use Mita\UranusSocketServer\Exceptions\RoutingException;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Mita\UranusSocketServer\Middlewares\MiddlewarePipeline;
use Mita\UranusSocketServer\Middlewares\RoutingMiddleware;
use Mita\UranusSocketServer\Packets\Packet;
use Mita\UranusSocketServer\Packets\PacketFactory;
use Mita\UranusSocketServer\Packets\PacketInterface;
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
    protected $routingMiddleware;
    protected $container;
    protected $packetFactory;

    public function __construct(
        ConnectionManager $connections, 
        RouterInterface $router, 
        RoutingMiddleware $routingMiddleware, 
        PacketFactory $packetFactory,
        ContainerInterface $container
    ) {
        $this->connections = $connections;
        $this->routingMiddleware = $routingMiddleware;
        $this->router = $router;
        $this->matcher = new UrlMatcher($router->getRouteCollection(), new RequestContext());
        $this->packetFactory = $packetFactory;
        $this->container = $container;
    }

    public function onOpen(ConnectionInterface $conn) {
        echo "New connection from {$conn->resourceId}\n";

        if (!$this->routingMiddleware->onOpen($conn)) {
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
        $packet = $this->packetFactory->createFromJson($msg);

        $this->routingMiddleware->handle($from, $packet, function (ConnectionInterface $conn, PacketInterface $packet, array $parameters) {
            $pipeline = new MiddlewarePipeline();

            if (isset($parameters['_middleware'])) {
                foreach ($parameters['_middleware'] as $middleware) {
                    $middlewareInstance = $this->container->get($middleware);
                    $pipeline->add($middlewareInstance);
                }
            }

            // Thực thi pipeline và cuối cùng gọi controller
            $pipeline->process($conn, $packet, function ($conn, $packet) use ($parameters) {
                $controller = $this->container->get($parameters['_controller']);
                if ($controller instanceof ControllerInterface) {
                    $controller->handle($conn, $packet, $parameters);
                    
                } else {
                    throw new RoutingException("Controller must implement ControllerInterface");
                }
            });
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
