<?php 
namespace Mita\UranusSocketServer\Configs;

use DI\ContainerBuilder;
use Mita\UranusSocketServer\Configs\Config;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Mita\UranusSocketServer\Middlewares\RoutingMiddleware;
use Mita\UranusSocketServer\Services\WebSocketService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class ServiceProvider {
    public function register(ContainerBuilder $containerBuilder, array $settings, array $userDiConfig = []) {
        $libraryDiConfig = [
            Config::class => function (ContainerInterface $container) use ($settings) {
                $config = new Config();
                $config->setConfig($settings);
                return $config;
            },
            Router::class => function (ContainerInterface $container) use ($settings) {
                $context = new RequestContext();
                $fileLocator = new FileLocator([dirname($settings['router_path'])]);
                $loader = new YamlFileLoader($fileLocator);
                return new Router($loader, basename($settings['router_path']), [], $context);
            },
            ConnectionManager::class => \DI\create(ConnectionManager::class),
            RoutingMiddleware::class => function (ContainerInterface $container) {
                return new RoutingMiddleware($container->get(Router::class), $container);
            },
            WebSocketService::class => function (ContainerInterface $container) {
                return new WebSocketService(
                    $container->get(ConnectionManager::class),
                    $container->get(Router::class),
                    $container->get(RoutingMiddleware::class), 
                    $container
                );
            }
        ];

        $combinedConfig = array_merge($libraryDiConfig, $userDiConfig);
        $containerBuilder->addDefinitions($combinedConfig);

        // Tự động đăng ký controller và middleware từ routes.yaml
        $this->registerRoutesAndServices($containerBuilder, $settings['router_path']);
    }

    private function registerRoutesAndServices(ContainerBuilder $containerBuilder, $routerPath) {
        $routes = Yaml::parseFile($routerPath);

        foreach ($routes as $route) {
            if (isset($route['controller'])) {
                $controller = explode('::', $route['controller'])[0];
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
}
