<?php 

namespace Mita\UranusSocketServer\Packets;

use Mita\UranusSocketServer\Exceptions\RoutingException;

class PacketFactory
{
    public function createFromJson(string $json): PacketInterface
    {
        $data = json_decode($json, true);
        if (!array_key_exists('route', $data) || !array_key_exists('msg', $data)) {
            throw new RoutingException("Invalid message format");
        }

        $metadata = $data;
        unset($metadata['msg'], $metadata['route']);

        return new Packet($data['route'], $data['msg'], $metadata);
    }
}
