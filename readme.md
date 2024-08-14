# UranusSocketServer Documentation

---

## Introduction

Welcome to the **UranusSocketServer** documentation, your comprehensive guide to building scalable and high-performance WebSocket applications in PHP. With its modular design, `UranusSocketServer` provides a robust foundation for managing WebSocket connections, implementing middleware, and handling events seamlessly.

This documentation will walk you through the installation process, setting up examples, and diving into the core APIs and customization options that `UranusSocketServer` offers.

---

## Getting Started with UranusSocketServer

### Installation and Setup

To get started with `UranusSocketServer`, follow these steps to install the library and run a simple chat application example.

#### 1. Clone the Repository

First, clone the `UranusSocketServer` repository to your local machine:

```bash
git clone https://github.com/your-repo/uranus-socket-server.git
cd uranus-socket-server
```

#### 2. Install Dependencies

Next, install the necessary dependencies using Composer:

```bash
composer install
```

#### 3. Navigate to the Example Directory

After installing the dependencies, navigate to the provided chat application example:

```bash
cd examples/chat
```

#### 4. Run the WebSocket Server

Start the WebSocket server by executing the following command:

```bash
php index.php
```

The server will start on `127.0.0.1:7654` by default. You should see a message in your terminal confirming that the server has started successfully.

---

## Example: Building a Chat Application

This section provides a step-by-step guide to building a simple chat application using `UranusSocketServer`.

### Directory Structure

The chat application is organized as follows:

```
.
└── chat
    ├── Controllers
    │   └── ChatController.php
    ├── Middlewares
    │   └── AuthMiddleware.php
    ├── Services
    │   └── ChatService.php
    ├── index.php
    ├── routes.yaml
    └── user_di_config.php
```

### Key Components

#### 1. **ChatController.php**

The `ChatController` handles the main chat functionalities, such as joining rooms and publishing messages to rooms.

```php
namespace Mita\UranusSocketServer\Examples\Chat\Controllers;

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
```

#### 2. **AuthMiddleware.php**

The `AuthMiddleware` provides a simple authentication layer before allowing access to the chat functionalities.

```php
namespace Mita\UranusSocketServer\Examples\Chat\Middlewares;

use Mita\UranusSocketServer\Middlewares\MiddlewareInterface;
use Mita\UranusSocketServer\Packets\PacketInterface;
use Ratchet\ConnectionInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)
    {
        // Example: Basic Auth logic here
        $next($conn, $packet);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        return true;
    }
}
```

#### 3. **ChatService.php**

The `ChatService` manages room subscriptions and broadcasts messages to all connections in a room.

```php 
namespace Mita\UranusSocketServer\Examples\Chat\Services;

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
```

#### 4. **index.php**

The `index.php` file is the entry point for the WebSocket server.

```php 
require __DIR__ . '/../../vendor/autoload.php';

use Mita\UranusSocketServer\SocketServer;

$settings = [
    'host' => '127.0.0.1',
    'port' => 7654,
    'router_path' => __DIR__ . '/routes.yaml'
];

$userDiConfig = require __DIR__ . '/user_di_config.php';

$socketServer = new SocketServer($settings, $userDiConfig);

$socketServer->registerEventListener('connection.added', function ($conn) {
    echo "New connection added: " . $conn->resourceId . "\n";
});

$socketServer->run();
```

#### 5. **routes.yaml**

The `routes.yaml` file defines the routes for joining rooms and publishing messages.

```yaml
join_room:
    path: /room/{roomId}/join
    controller: Mita\UranusSocketServer\Examples\Chat\Controllers\ChatController
    defaults:
        _middleware:
            - Mita\UranusSocketServer\Examples\Chat\Middlewares\AuthMiddleware
room_publish:
    path: /room/{roomId}/publish
    controller: Mita\UranusSocketServer\Examples\Chat\Controllers\ChatController
```

#### 6. **user_di_config.php**

The `user_di_config.php` file allows you to define any custom Dependency Injection configurations.

```php
<?php

return [
    // Custom Dependency Injection configuration
];
```

---

## API Documentation

The following sections detail the core APIs provided by `UranusSocketServer` for building your WebSocket applications.

### 1. **SocketServer Class**

The `SocketServer` class is the primary entry point for running the WebSocket server.

**Constructor:**

```php
public function __construct(array $settings = [], array $userDiConfig = [])
```

- **$settings**: An associative array containing server configurations such as `host`, `port`, and `router_path`.
- **$userDiConfig**: An associative array of user-defined Dependency Injection configurations.

**Methods:**

- **run()**

  Starts the WebSocket server.

  ```php
  public function run()
  ```

- **registerEventListener(string $eventName, callable $listener)**

  Registers a listener for a specific event.

  ```php
  public function registerEventListener(string $eventName, callable $listener)
  ```

### 2. **RoutingMiddleware Class**

The `RoutingMiddleware` class routes incoming messages to the appropriate controller based on the route configuration.

**Methods:**

- **handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)**

  Routes the incoming messages to the correct controller.

  ```php
  public function handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)
  ```

- **onOpen(ConnectionInterface $conn)**

  Handles the connection opening event.

  ```php
  public function onOpen(ConnectionInterface $conn)
  ```

### 3. **MiddlewarePipeline Class**

The `MiddlewarePipeline` class processes messages through a series of middleware.

**Methods:**

- **add(MiddlewareInterface $middleware)**

  Adds a middleware to the pipeline.

  ```php
  public function add(MiddlewareInterface $middleware)
  ```

- **process(ConnectionInterface $conn, PacketInterface $packet, callable $finalHandler)**

  Processes the packet through the pipeline and finally calls the controller.

  ```php
  public function process(ConnectionInterface $conn, PacketInterface

 $packet, callable $finalHandler)
  ```

### 4. **ConnectionManager Class**

The `ConnectionManager` class manages WebSocket connections and their subscriptions.

**Methods:**

- **add(ConnectionInterface $conn)**

  Adds a new connection to the manager.

  ```php
  public function add(ConnectionInterface $conn)
  ```

- **remove(ConnectionInterface $conn)**

  Removes a connection from the manager.

  ```php
  public function remove(ConnectionInterface $conn)
  ```

- **subscribe(ConnectionInterface $conn, string $route)**

  Subscribes a connection to a specific route.

  ```php
  public function subscribe(ConnectionInterface $conn, string $route)
  ```

- **unsubscribe(ConnectionInterface $conn, string $route)**

  Unsubscribes a connection from a specific route.

  ```php
  public function unsubscribe(ConnectionInterface $conn, string $route)
  ```

- **sendToRoute(string $route, string $message)**

  Sends a message to all connections subscribed to a specific route.

  ```php
  public function sendToRoute(string $route, string $message)
  ```

### 5. **EventDispatcher Class**

The `EventDispatcher` class allows for the registration and dispatching of events.

**Methods:**

- **addListener(string $eventName, callable $listener)**

  Adds a listener for a specific event.

  ```php
  public function addListener(string $eventName, callable $listener)
  ```

- **dispatch(string $eventName, $eventData = null)**

  Dispatches an event to all registered listeners.

  ```php
  public function dispatch(string $eventName, $eventData = null)
  ```

### 6. **Packet Class**

The `Packet` class represents a WebSocket message packet, encapsulating the route, message, and metadata.

**Methods:**

- **getRoute()**

  Returns the route associated with the packet.

  ```php
  public function getRoute(): string
  ```

- **getMessage()**

  Returns the message content of the packet.

  ```php
  public function getMessage()
  ```

- **getMetadata(string $key = null)**

  Retrieves metadata associated with the packet. If no key is provided, returns all metadata.

  ```php
  public function getMetadata(string $key = null)
  ```

- **setMetadata(string $key, $value)**

  Sets a metadata key-value pair.

  ```php
  public function setMetadata(string $key, $value): void
  ```

### 7. **PacketFactory Class**

The `PacketFactory` class creates `Packet` instances from JSON strings.

**Methods:**

- **createFromJson(string $json): PacketInterface**

  Creates a `Packet` instance from a JSON string.

  ```php
  public function createFromJson(string $json): PacketInterface
  ```

---

## Conclusion

Thank you for choosing **UranusSocketServer** for your WebSocket application development. We believe that with its flexible architecture, customizable middleware pipeline, and comprehensive event management system, `UranusSocketServer` will empower you to build powerful, scalable, and efficient WebSocket solutions with ease.

We encourage you to explore the library further, customize the provided examples, and contribute to the project. Your feedback and contributions are invaluable in helping us improve and adapt `UranusSocketServer` to meet the evolving needs of the developer community.

Happy coding, and may your WebSocket applications thrive!