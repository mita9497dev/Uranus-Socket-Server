<?php 

namespace Mita\UranusSocketServer\Plugins;

use Mita\UranusSocketServer\Events\EventDispatcher;
use Mita\UranusSocketServer\Events\EventDispatcherInterface;

class PluginManager
{
    protected $plugins = [];

    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[] = $plugin;
    }

    public function registerPlugins(EventDispatcherInterface $dispatcher)
    {
        foreach ($this->plugins as $plugin) {
            $plugin->register($dispatcher);
            $dispatcher->dispatch('plugin.registered', $plugin);
        }
    }

    public function bootPlugins()
    {
        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }
    }
}