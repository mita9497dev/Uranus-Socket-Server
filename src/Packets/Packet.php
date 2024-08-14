<?php 
namespace Mita\UranusSocketServer\Packets;

class Packet implements PacketInterface
{
    protected $route;
    protected $message;
    protected $metadata;

    public function __construct(string $route, $message, array $metadata = [])
    {
        $this->route = $route;
        $this->message = $message;
        $this->metadata = $metadata;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getMetadata(string $key = null)
    {
        if ($key !== null) {
            return $this->metadata[$key] ?? null;
        }
        return $this->metadata;
    }

    public function setMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }
}