<?php
namespace Mita\UranusSocketServer;

use DI\ContainerBuilder;
use Mita\UranusSocketServer\Configs\ServiceProvider;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Mita\UranusSocketServer\Middlewares\RoutingMiddleware;
use Mita\UranusSocketServer\Services\WebSocketService;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Router;

class SocketServer {
    protected $container;
    private $host;
    private $port;

    public function __construct(array $settings = [], array $userDiConfig = []) {
        // Default settings
        $defaultSettings = [
            'host'          => '127.0.0.1',
            'port'          => 8080,
            'router_path'   => __DIR__ . DIRECTORY_SEPARATOR . 'routes.yaml'
        ];

        // Merge user settings with defaults
        $settings = array_merge($defaultSettings, $settings);

        // Initialize the DI container
        $containerBuilder = new ContainerBuilder();

        // Register services via service provider
        $serviceProvider = new ServiceProvider();
        $serviceProvider->register($containerBuilder, $settings, $userDiConfig);

        // Build the container
        $this->container = $containerBuilder->build();

        // Set host, port, and router path
        $this->host = $settings['host'];
        $this->port = $settings['port'];
    }

    public function run() {
        $connectionManager = $this->container->get(ConnectionManager::class);
        $routingMiddleware = $this->container->get(RoutingMiddleware::class);
        $router = $this->container->get(Router::class);

        // Create the WebSocketService
        $webSocketService = new WebSocketService($connectionManager, $router, $routingMiddleware, $this->container);

        // Create and run the WebSocket server
        $server = IoServer::factory(new HttpServer(new WsServer($webSocketService)), $this->port, $this->host);

        echo "WebSocket server started on {$this->host}:{$this->port}\n";
        $server->run();
    }
}