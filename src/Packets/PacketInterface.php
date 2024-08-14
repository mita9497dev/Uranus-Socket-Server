<?php 

namespace Mita\UranusSocketServer\Packets;

interface PacketInterface
{
    public function getRoute(): string;
    public function getMessage();
    public function getMetadata(string $key = null);
    public function setMetadata(string $key, $value): void;
}