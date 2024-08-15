<?php 
namespace Mita\UranusSocketServer\Examples\ChatWithAuth\Plugins;

use Mita\UranusSocketServer\Middlewares\MiddlewareInterface;
use Mita\UranusSocketServer\Packets\PacketInterface;
use Mita\UranusSocketServer\Middlewares\MiddlewarePipeline;
use Mita\UranusSocketServer\Events\EventDispatcherInterface;
use Mita\UranusSocketServer\Plugins\PluginInterface;
use Ratchet\ConnectionInterface;

class AuthPlugin implements PluginInterface, MiddlewareInterface
{
    protected $secretKey;

    protected $keyName;

    public function __construct($secretKey, $keyName = 'access_token')
    {
        $this->secretKey = $secretKey;
        $this->keyName = $keyName;
    }

    public function register(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener('middleware.register', [$this, 'onRegisterMiddleware']);
        $dispatcher->addListener('connection.open', [$this, 'onOpen']);
    }

    public function boot()
    {
        
    }

    public function unregister(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->removeListener('middleware.register', [$this, 'onRegisterMiddleware']);
        $dispatcher->removeListener('connection.open', [$this, 'onOpen']);
    }

    public function onRegisterMiddleware(MiddlewarePipeline $pipeline)
    {
        $pipeline->add($this);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $uri = $conn->httpRequest->getUri();
        parse_str($uri->getQuery(), $params);
        $token = $params[$this->keyName] ?? null;
        
        if (!$this->validateToken($token)) {
            $conn->send("Invalid token");
            $conn->close();
            throw new \Exception("Invalid token");
        }
    }

    public function handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)
    {
        $token = $packet->getMetadata($this->keyName);

        if (!$this->validateToken($token)) {
            $conn->send("Invalid token");
            $conn->close();
            return;
        }

        $next($conn, $packet);
    }

    protected function decodeToken($token)
    {
        // You can decode the token here by using base64_decode or any other method
        // here we are using base64_decode
        return base64_decode($token);
    }

    protected function encodeToken($data)
    {
        // You can encode the data here by using base64_encode or any other method
        // here we are using base64_encode
        return base64_encode($data);
    }

    protected function validateToken($token)
    {
        if (!$token) {
            return false;
        }

        // You can decode the token and validate the data here
        // $token = $this->decodeToken($token);
        // return $token == $this->secretKey;
        // here we are just comparing the token with the secret key

        return $token == $this->secretKey;
    }
}
