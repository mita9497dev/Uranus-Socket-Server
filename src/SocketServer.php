<?php
namespace Mita\UranusSocketServer;

use DI\ContainerBuilder;
use Mita\UranusSocketServer\Configs\ServiceProvider;
use Mita\UranusSocketServer\Events\EventDispatcherInterface;
use Mita\UranusSocketServer\Middlewares\MiddlewarePipeline;
use Mita\UranusSocketServer\Plugins\PluginInterface;
use Mita\UranusSocketServer\Plugins\PluginManager;
use Mita\UranusSocketServer\Services\WebSocketService;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class SocketServer 
{
    protected $container;

    protected $pluginManager;

    private $host;
    private $port;

    public function __construct(array $settings = [], array $userDiConfig = []) 
    {
        // Default settings
        $defaultSettings = [
            'host'          => '127.0.0.1',
            'port'          => 8080,
            'router_path'   => __DIR__ . DIRECTORY_SEPARATOR . 'routes.yaml'
        ];

        $settings = array_merge($defaultSettings, $settings);

        $containerBuilder = new ContainerBuilder();
        $serviceProvider = new ServiceProvider();
        $serviceProvider->register($containerBuilder, $settings, $userDiConfig);

        /** @var ContainerInterface */
        $this->container = $containerBuilder->build();

        /** @var PluginManager */
        $this->pluginManager = $this->container->get(PluginManager::class);

        $this->host = $settings['host'];
        $this->port = $settings['port'];
    }

    public function addPlugin(PluginInterface $plugin)
    {
        $this->pluginManager->addPlugin($plugin);
    }

    public function run() 
    {
        
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        
        $this->pluginManager->registerPlugins($dispatcher);
        $this->pluginManager->bootPlugins();

        // Kích hoạt sự kiện 'server.boot'
        $dispatcher->dispatch('server.boot');

        /** @var MiddlewarePipeline $middlewarePipeline */
        $middlewarePipeline = $this->container->get(MiddlewarePipeline::class);

        // Kích hoạt sự kiện 'middleware.register'
        $dispatcher->dispatch('middleware.register', $middlewarePipeline);

        /** @var WebSocketService $webSocketService */
        $webSocketService = $this->container->get(WebSocketService::class);
        
        $server = IoServer::factory(new HttpServer(new WsServer($webSocketService)), $this->port, $this->host);

        echo "WebSocket server started on {$this->host}:{$this->port}\n";
        $server->run();
    }

    public function registerEventListener(string $eventName, callable $listener)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $dispatcher->addListener($eventName, $listener);
    }
}
