<?php 

namespace Mita\UranusSocketServer\Plugins;

use Mita\UranusSocketServer\Events\EventDispatcherInterface;

interface PluginInterface
{
    public function register(EventDispatcherInterface $dispatcher);
    public function boot();
    public function unregister(EventDispatcherInterface $dispatcher);
}

