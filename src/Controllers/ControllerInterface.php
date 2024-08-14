<?php 
namespace Mita\UranusSocketServer\Controllers;

use Mita\UranusSocketServer\Packets\PacketInterface;
use Ratchet\ConnectionInterface;

interface ControllerInterface
{
    public function handle(ConnectionInterface $conn, PacketInterface $message, array $params);
}
