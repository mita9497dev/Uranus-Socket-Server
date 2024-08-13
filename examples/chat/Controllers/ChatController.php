<?php

namespace Examples\Controllers;

use Mita\UranusSocketServer\Controllers\ControllerInterface;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Ratchet\ConnectionInterface;

class ChatController implements ControllerInterface
{
    private ConnectionManager $connectionManager; 

    public function __construct(ConnectionManager $connectionManager) {
        $this->$connectionManager = $connectionManager;
    }

    public function subscribe(ConnectionInterface $conn, $message, $params)
    {
        $this->connectionManager->subscribe($conn, $params['roomId']);
    }

    public function publish(ConnectionInterface $conn, $message, $params)
    {
        $this->connectionManager->sendToRoute($params['roomId'], $message);
    }
}
