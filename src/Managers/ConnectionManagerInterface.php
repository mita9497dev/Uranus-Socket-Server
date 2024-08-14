<?php 

namespace Mita\UranusSocketServer\Managers;

use Ratchet\ConnectionInterface;

interface ConnectionManagerInterface
{
    public function add(ConnectionInterface $conn);
    public function remove(ConnectionInterface $conn);
    public function get($resourceId);
    public function getAll();
    public function subscribe(ConnectionInterface $conn, $route);
    public function unsubscribe(ConnectionInterface $conn, $route);
    public function getSubscriptions(ConnectionInterface $conn);
    public function getSubscribers($route);
    public function isSubscribed(ConnectionInterface $conn, $route);
}
