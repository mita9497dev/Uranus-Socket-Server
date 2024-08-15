<?php 

namespace Mita\UranusSocketServer\Examples\ChatWithAuth\Services;

use Mita\UranusSocketServer\Managers\ConnectionManager;
use Ratchet\ConnectionInterface;

class ChatService
{
    protected ConnectionManager $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function sendMessageToRoom($roomId, $message)
    {
        $subscribers = $this->connectionManager->getSubscribers($roomId);
        foreach ($subscribers as $subscriber) {
            $subscriber->send($message);
        }
    }
    
    public function isJoined(ConnectionInterface $conn, $roomId)
    {
        return $this->connectionManager->isSubscribed($conn, $roomId);
    }

    public function joinRoom(ConnectionInterface $conn, $roomId)
    {
        $this->connectionManager->subscribe($conn, $roomId);
    }

    public function broadcastMessage($message)
    {
        foreach ($this->connectionManager->getAll() as $connection) {
            $connection->send($message);
        }
    }
}
