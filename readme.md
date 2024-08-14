### UranusSocketServer Documentation

---

#### Overview

`UranusSocketServer` is a PHP library designed for building WebSocket applications. It provides essential tools such as Middleware Pipeline, Event Dispatcher, and Connection Manager to efficiently manage connections and handle messages.

This documentation outlines the key APIs and methods provided by the library, allowing users to customize and extend their WebSocket servers.

---

### API Documentation

#### 1. **SocketServer Class**

The `SocketServer` class is the main entry point for running the WebSocket server.

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

#### 2. **RoutingMiddleware Class**

The `RoutingMiddleware` class is responsible for routing incoming messages to the appropriate controller based on the route configuration.

**Methods:**

- **handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)**

  Handles the routing of incoming messages.

  ```php
  public function handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)
  ```

- **onOpen(ConnectionInterface $conn)**

  Handles the connection opening event.

  ```php
  public function onOpen(ConnectionInterface $conn)
  ```

#### 3. **MiddlewarePipeline Class**

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
  public function process(ConnectionInterface $conn, PacketInterface $packet, callable $finalHandler)
  ```

#### 4. **ConnectionManager Class**

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

#### 5. **EventDispatcher Class**

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

#### 6. **Packet Class**

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

#### 7. **PacketFactory Class**

The `PacketFactory` class creates `Packet` instances from JSON strings.

**Methods:**

- **createFromJson(string $json): PacketInterface**

  Creates a `Packet` instance from a JSON string.

  ```php
  public function createFromJson(string $json): PacketInterface
  ```

---

### Example: Chat Application

#### Installation and Setup

To demonstrate the capabilities of the `UranusSocketServer` library, we've provided a simple chat application example. Follow these steps to install and run the example:

1. **Clone the Repository**
   If you haven't already cloned the `UranusSocketServer` repository, do so by running the following command:

   ```bash
   git clone https://github.com/your-repo/uranus-socket-server.git
   cd uranus-socket-server
   ```

2. **Install Dependencies**
   The example requires the necessary dependencies to be installed. Navigate to the root directory of the project and install the dependencies using Composer:

   ```bash
   composer install
   ```

3. **Navigate to the Example Directory**
   Once the dependencies are installed, navigate to the `chat` example directory:

   ```bash
   cd examples/chat
   ```

4. **Run the WebSocket Server**
   Start the WebSocket server by running the following command:

   ```bash
   php index.php
   ```

   The server will start on the host and port specified in the `index.php` file (default is `127.0.0.1:7654`). You should see a message in the console indicating that the WebSocket server has started successfully.

5. **Test the Chat Application**
   You can test the chat application using a WebSocket client (e.g., a WebSocket testing tool, a web-based client, or a custom client implementation). Connect to the WebSocket server using the address `ws://127.0.0.1:7654`.

   Once connected, you can join a room by sending a message with the route `/room/{roomId}/join` and publish messages to the room using `/room/{roomId}/publish`.

   Here's an example of a JSON payload to join a room:

   ```json
   {
       "route": "/room/123/join",
       "msg": "Joining room 123"
   }
   ```

   And an example to publish a message in the room:

   ```json
   {
       "route": "/room/123/publish",
       "msg": "Hello, everyone!"
   }
   ```

#### Directory Structure

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

#### 1. **ChatController.php**

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

```php 
require __DIR__ . '/../../vendor/autoload.php';

use Mita\UranusSocketServer\SocketServer;

$settings = [
    'host' => '127.0.0.1',
    'port' => 7654,
    'router_path' => __DIR__ . '/routes.yaml'
];

$userDiConfig = require __DIR__ . '/user_di

_config.php';

$socketServer = new SocketServer($settings, $userDiConfig);

$socketServer->registerEventListener('connection.added', function ($conn) {
    echo "New connection added: " . $conn->resourceId . "\n";
});

$socketServer->run();
```

#### 5. **routes.yaml**

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

```php
<?php

return [
    // Custom Dependency Injection configuration
];
```

#### Conclusion

Thank you for choosing `UranusSocketServer` for your WebSocket application development. We hope this documentation has provided you with the insights and guidance needed to make the most of the library's features. With its flexible architecture, customizable middleware pipeline, and comprehensive event management system, `UranusSocketServer` empowers you to build scalable and high-performance WebSocket solutions with ease.

As you continue to explore and develop with `UranusSocketServer`, we encourage you to contribute to the project, share your experiences, and provide feedback. Together, we can continue to improve and evolve the library to meet the ever-changing needs of the WebSocket development community.

Happy coding!