<?php 

namespace Mita\UranusSocketServer\Events;

interface EventDispatcherInterface
{
    public function addListener(string $eventName, callable $listener);
    public function removeListener(string $eventName, callable $listener);
    public function dispatch(string $eventName, $eventData = null);
}
