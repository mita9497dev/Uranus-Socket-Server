<?php
namespace Mita\UranusSocketServer\Managers;

use Mita\UranusSocketServer\Events\EventDispatcherInterface;
use Ratchet\ConnectionInterface;

class ConnectionManager implements ConnectionManagerInterface {
    protected $connections = [];
    protected $subscriptions = [];
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function add(ConnectionInterface $conn)
    {
        $this->connections[$conn->resourceId] = $conn;
        $this->eventDispatcher->dispatch('connection.added', $conn);
    }

    public function remove(ConnectionInterface $conn)
    {
        unset($this->connections[$conn->resourceId]);
        unset($this->subscriptions[$conn->resourceId]);
        $this->eventDispatcher->dispatch('connection.removed', $conn);
    }

    public function get($resourceId)
    {
        return $this->connections[$resourceId] ?? null;
    }

    public function getAll()
    {
        return $this->connections;
    }

    public function subscribe(ConnectionInterface $conn, $route)
    {
        $this->subscriptions[$conn->resourceId][$route] = true;
        $this->eventDispatcher->dispatch('connection.subscribed', ['conn' => $conn, 'route' => $route]);
    }

    public function unsubscribe(ConnectionInterface $conn, $route)
    {
        unset($this->subscriptions[$conn->resourceId][$route]);
        $this->eventDispatcher->dispatch('connection.unsubscribed', ['conn' => $conn, 'route' => $route]);
    }

    public function getSubscriptions(ConnectionInterface $conn)
    {
        return $this->subscriptions[$conn->resourceId] ?? [];
    }

    public function isSubscribed(ConnectionInterface $conn, $route)
    {
        return isset($this->subscriptions[$conn->resourceId][$route]);
    }

    public function getSubscribers($route)
    {
        $subscribers = [];
        foreach ($this->subscriptions as $resourceId => $routes) {
            if (isset($routes[$route])) {
                $subscribers[] = $this->get($resourceId);
            }
        }
        return $subscribers;
    }

    public function sendToRoute($route, $message)
    {
        foreach ($this->subscriptions as $resourceId => $routes) {
            if (isset($routes[$route])) {
                $conn = $this->get($resourceId);
                if ($conn) {
                    $conn->send($message);
                }
            }
        }
        $this->eventDispatcher->dispatch('message.sent', ['route' => $route, 'message' => $message]);
    }
}

