<?php

namespace Mita\UranusSocketServer\Examples\ChatWithAuth\Controllers;

use Mita\UranusSocketServer\Controllers\BaseController;
use Mita\UranusSocketServer\Examples\Chat\Services\ChatService;
use Mita\UranusSocketServer\Managers\ConnectionManager;
use Mita\UranusSocketServer\Packets\PacketInterface;
use Ratchet\ConnectionInterface;

class ChatController extends BaseController
{
    protected ChatService $chatService;

    public function __construct(ConnectionManager $connectionManager, ChatService $chatService)
    {
        parent::__construct($connectionManager);
        $this->chatService = $chatService;
    }

    public function handle(ConnectionInterface $conn, PacketInterface $packet, array $params)
    {
        if ($params['_route'] === 'join_room') {
            if ($this->chatService->isJoined($conn, $params['roomId'])) {
                $conn->send("You are already in the room");
                return;
            }
            $this->chatService->joinRoom($conn, $params['roomId']);
            echo "User {$conn->resourceId} joined room {$params['roomId']}\n";

        } elseif ($params['_route'] === 'room_publish') {
            if (!$this->chatService->isJoined($conn, $params['roomId'])) {
                $conn->send("You are not in the room");
                return;
            }
            
            $this->chatService->sendMessageToRoom($params['roomId'], $packet->getMessage());
            echo "User {$conn->resourceId} sent message to room {$params['roomId']}\n";
        }
    }
}
