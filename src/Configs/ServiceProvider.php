<?php

namespace Mita\UranusSocketServer\Configs;

use DI\ContainerBuilder;
use Mita\UranusSocketServer\Configs\Config;
use Mita\UranusSocketServer\Events\EventDispatcher;
use Mita\UranusSocketServer\Events\EventDispatcherInterface;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Mita\UranusSocketServer\Middlewares\MiddlewarePipeline;
use Mita\UranusSocketServer\Middlewares\RoutingMiddleware;
use Mita\UranusSocketServer\Packets\PacketFactory;
use Mita\UranusSocketServer\Plugins\PluginManager;
use Mita\UranusSocketServer\Services\WebSocketService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class ServiceProvider
{
    public function register(ContainerBuilder $containerBuilder, array $settings, array $userDiConfig = [])
    {
        $this->registerConfig($containerBuilder, $settings);
        $this->registerRouter($containerBuilder, $settings);
        $this->registerPacketFactory($containerBuilder);
        $this->registerEventDispatcher($containerBuilder);
        $this->registerConnectionManager($containerBuilder);
        $this->registerRoutingMiddleware($containerBuilder);
        $this->registerWebSocketService($containerBuilder);
        $this->registerMiddlewarePipeline($containerBuilder);

        $containerBuilder->addDefinitions($userDiConfig);

        $this->registerRoutesAndServices($containerBuilder, $settings['router_path']);
    }

    protected function registerConfig(ContainerBuilder $containerBuilder, array $settings)
    {
        $containerBuilder->addDefinitions([
            Config::class => function (ContainerInterface $container) use ($settings) {
                $config = new Config();
                $config->setConfig($settings);
                return $config;
            }
        ]);
    }

    protected function registerRouter(ContainerBuilder $containerBuilder, array $settings)
    {
        $containerBuilder->addDefinitions([
            Router::class => function (ContainerInterface $container) use ($settings) {
                $context = new RequestContext();
                $fileLocator = new FileLocator([dirname($settings['router_path'])]);
                $loader = new YamlFileLoader($fileLocator);
                return new Router($loader, basename($settings['router_path']), [], $context);
            }
        ]);
    }

    protected function registerEventDispatcher(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            EventDispatcherInterface::class => \DI\create(EventDispatcher::class),
        ]);
    }

    protected function registerConnectionManager(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            ConnectionManager::class => function (ContainerInterface $container) {
                return new ConnectionManager($container->get(EventDispatcherInterface::class));
            }
        ]);
    }

    protected function registerRoutingMiddleware(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            RoutingMiddleware::class => function (ContainerInterface $container) {
                return new RoutingMiddleware($container->get(Router::class), $container->get(EventDispatcherInterface::class));
            }
        ]);
    }

    protected function registerPacketFactory(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            PacketFactory::class => \DI\create(PacketFactory::class)
        ]);
    }

    protected function registerWebSocketService(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            WebSocketService::class => function (ContainerInterface $container) {
                return new WebSocketService(
                    $container->get(ConnectionManager::class),
                    $container->get(Router::class),
                    $container->get(RoutingMiddleware::class),
                    $container->get(PacketFactory::class), 
                    $container->get(EventDispatcherInterface::class),
                    $container
                );
            }
        ]);
    }

    protected function registerRoutesAndServices(ContainerBuilder $containerBuilder, $routerPath)
    {
        $routes = Yaml::parseFile($routerPath);

        foreach ($routes as $route) {
            if (isset($route['controller'])) {
                $controller = trim($route['controller']);
                $containerBuilder->addDefinitions([
                    $controller => \DI\autowire($controller)
                ]);
            }

            if (isset($route['middleware'])) {
                foreach ($route['middleware'] as $middleware) {
                    $containerBuilder->addDefinitions([
                        $middleware => \DI\autowire($middleware)
                    ]);
                }
            }
        }
    }

    public function registerMiddlewarePipeline(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            MiddlewarePipeline::class => \DI\create(MiddlewarePipeline::class)
        ]);
    }

    public function registerPluginManager(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addDefinitions([
            PluginManager::class => \DI\create(PluginManager::class)
        ]);
    }
}
