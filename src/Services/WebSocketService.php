<?php

namespace Mita\UranusSocketServer\Services;

use Mita\UranusSocketServer\Controllers\ControllerInterface;
use Mita\UranusSocketServer\Events\EventDispatcherInterface;
use Mita\UranusSocketServer\Exceptions\RoutingException;
use Mita\UranusSocketServer\Managers\ConnectionManagerInterface;
use Mita\UranusSocketServer\Middlewares\MiddlewarePipeline;
use Mita\UranusSocketServer\Middlewares\RoutingMiddleware;
use Mita\UranusSocketServer\Packets\PacketFactory;
use Mita\UranusSocketServer\Packets\PacketInterface;
use Psr\Container\ContainerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class WebSocketService implements MessageComponentInterface
{
    protected $connections;
    protected $router;
    protected $matcher;
    protected $routingMiddleware;
    protected $container;
    protected $packetFactory;
    protected $eventDispatcher;

    public function __construct(
        ConnectionManagerInterface $connections,
        RouterInterface $router,
        RoutingMiddleware $routingMiddleware,
        PacketFactory $packetFactory,
        EventDispatcherInterface $eventDispatcher,
        ContainerInterface $container
    ) {
        $this->connections = $connections;
        $this->routingMiddleware = $routingMiddleware;
        $this->router = $router;
        $this->matcher = new UrlMatcher($router->getRouteCollection(), new RequestContext());
        $this->packetFactory = $packetFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        try {
            $this->eventDispatcher->dispatch('connection.opened', $conn);

            $this->routingMiddleware->onOpen($conn);
            $this->connections->add($conn);
        } catch (\Exception $e) {
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
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->eventDispatcher->dispatch('message.received', ['connection' => $from, 'message' => $msg]);

        $packet = $this->packetFactory->createFromJson($msg);

        $this->routingMiddleware->handle($from, $packet, function (ConnectionInterface $conn, PacketInterface $packet, array $parameters) {
            /** @var MiddlewarePipeline $middlewarePipeline */
            $middlewarePipeline = $this->container->get(MiddlewarePipeline::class);
            $pipeline = new MiddlewarePipeline($middlewarePipeline->getMiddlewares());

            if (isset($parameters['_middleware'])) {
                foreach ($parameters['_middleware'] as $middleware) {
                    $middlewareInstance = $this->container->get($middleware);
                    $pipeline->add($middlewareInstance);
                }
            }

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

    public function onClose(ConnectionInterface $conn)
    {
        echo "Connection {$conn->resourceId} closed\n";
        $this->connections->remove($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error on connection {$conn->resourceId}: " . $e->getMessage() . "\n";
        $conn->close();
    }
}
