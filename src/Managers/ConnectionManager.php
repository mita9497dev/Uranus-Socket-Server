<?php
namespace Mita\UranusSocketServer\Managers;

use Ratchet\ConnectionInterface;

class ConnectionManager {
    protected $connections = [];
    protected $subscriptions = [];

    public function add(ConnectionInterface $conn) {
        $this->connections[$conn->resourceId] = $conn;
    }

    public function remove(ConnectionInterface $conn) {
        unset($this->connections[$conn->resourceId]);
        unset($this->subscriptions[$conn->resourceId]);
    }

    public function get($resourceId) {
        return $this->connections[$resourceId] ?? null;
    }

    public function getAll() {
        return $this->connections;
    }

    public function subscribe(ConnectionInterface $conn, $route) {
        $this->subscriptions[$conn->resourceId][$route] = true;
    }

    public function unsubscribe(ConnectionInterface $conn, $route) {
        unset($this->subscriptions[$conn->resourceId][$route]);
    }

    public function getSubscriptions(ConnectionInterface $conn) {
        return $this->subscriptions[$conn->resourceId] ?? [];
    }

    public function getSubscribers($route) {
        $subscribers = [];
        foreach ($this->subscriptions as $resourceId => $routes) {
            if (isset($routes[$route])) {
                $subscribers[] = $this->get($resourceId);
            }
        }
        return $subscribers;
    }

    public function sendToRoute($route, $message) {
        foreach ($this->subscriptions as $resourceId => $routes) {
            if (isset($routes[$route])) {
                $conn = $this->get($resourceId);
                if ($conn) {
                    $conn->send($message);
                }
            }
        }
    }
}
