<?php 

namespace Mita\UranusSocketServer\Events;

class EventDispatcher implements EventDispatcherInterface
{
    protected $listeners = [];

    public function addListener(string $eventName, callable $listener)
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function removeListener(string $eventName, callable $listener)
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $key => $registeredListener) {
                if ($registeredListener === $listener) {
                    unset($this->listeners[$eventName][$key]);
                    return;
                }
            }
        }
    }

    public function dispatch(string $eventName, $eventData = null)
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($eventData);
            }
        }
    }
}
