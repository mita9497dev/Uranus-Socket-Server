# UranusSocketServer

**UranusSocketServer** is a powerful, scalable, and easy-to-use PHP library designed for building high-performance WebSocket applications. With features like a flexible middleware pipeline, comprehensive event management, and efficient connection handling, UranusSocketServer empowers developers to create sophisticated WebSocket solutions effortlessly.

## Features

- **Modular Architecture**: Easily extend and customize your WebSocket server.
- **Dependency Injection**: Fully DI-compliant for clean and maintainable code.
- **Scalability**: Efficiently handles numerous simultaneous WebSocket connections.
- **Customizable Routing**: Route messages to controllers with support for middleware.
- **Event-Driven**: Hook into key lifecycle events for enhanced control.

## Quick Start

### Installation

Install the library using Composer:

```bash
composer require mita9497dev/uranus-socket-server
```

### Running the Basic Chat Application Example

To get a feel for how UranusSocketServer works, you can try out the included basic chat application example.

#### 1. **Clone the Repository:**

   ```bash
   git clone https://github.com/mita9497dev/uranus-socket-server.git
   cd uranus-socket-server
   ```

#### 2. **Install Dependencies:**

   ```bash
   composer install
   ```

#### 3. **Navigate to the Example Directory:**

   ```bash
   cd examples/chat
   ```

#### 4. **Introduction to `index.php`:**

   The `index.php` file is the entry point for the WebSocket server. It sets up the server with minimal configuration, demonstrating how easy it is to get started with UranusSocketServer:

   ```php
   require __DIR__ . '/vendor/autoload.php';

   use Mita\UranusSocketServer\SocketServer;

   $settings = [
       'host' => '127.0.0.1',
       'port' => 7654,
       'router_path' => __DIR__ . '/routes.yaml'
   ];

   $socketServer = new SocketServer($settings);
   $socketServer->run();
   ```

#### 5. **Run the WebSocket Server:**

   Start the WebSocket server with the following command:

   ```bash
   php index.php
   ```

#### 6. **Connect to the Server:**

   Use a WebSocket client to connect to `ws://127.0.0.1:7654`. You can then join a room and send messages using JSON payloads:

   **Join a Room:**

   ```json
   {
       "route": "/room/123/join",
       "msg": "Joining room 123"
   }
   ```

   **Send a Message to the Room:**

   ```json
   {
       "route": "/room/123/publish",
       "msg": "Hello, everyone!"
   }
   ```

### Running the ChatWithAuth Application Example

Building on the basic chat functionality, this example adds an authentication layer. The **ChatWithAuth** example requires users to authenticate with a token before they can join a chat room or send messages.

#### 1. **Clone the Repository:**

   ```bash
   git clone https://github.com/mita9497dev/uranus-socket-server.git
   cd uranus-socket-server
   ```

#### 2. **Install Dependencies:**

   ```bash
   composer install
   ```

#### 3. **Navigate to the Example Directory:**

   Navigate to the `ChatWithAuth` example directory:

   ```bash
   cd examples/ChatWithAuth
   ```

#### 4. **Introduction to `index.php`:**

   Similar to the basic chat example, the `index.php` file in the `ChatWithAuth` example is straightforward, but it includes an additional step to register an authentication plugin:

   ```php
   require __DIR__ . '/../../vendor/autoload.php';

   use Mita\UranusSocketServer\Examples\ChatWithAuth\Plugins\AuthPlugin;
   use Mita\UranusSocketServer\SocketServer;

   $settings = [
       'host' => '127.0.0.1',
       'port' => 7654,
       'router_path' => __DIR__ . '/routes.yaml'
   ];

   $socketServer = new SocketServer($settings);

   $authPlugin = new AuthPlugin('your_secret_key');
   $socketServer->addPlugin($authPlugin);

   $socketServer->run();
   ```

#### 5. **Update the Secret Key:**

   Replace `'your_secret_key'` with your desired secret key for authentication.

#### 6. **Run the WebSocket Server:**

   Start the WebSocket server:

   ```bash
   php index.php
   ```

#### 7. **Connect to the Server:**

   Use a WebSocket client to connect to `ws://127.0.0.1:7654`, including the `access_token` in the URI query string.

   **Example Connection URI:**

   ```plaintext
   ws://127.0.0.1:7654/?access_token=your_secret_key
   ```

#### 8. **Join a Room:**

   Authenticate and join a chat room with the following JSON payload:

   ```json
   {
       "route": "/room/123/join",
       "msg": "Joining room 123"
   }
   ```

#### 9. **Send a Message to the Room:**

   Once authenticated and joined, send a message to the room:

   ```json
   {
       "route": "/room/123/publish",
       "msg": "Hello, authenticated users!"
   }
   ```

### Explanation of `AuthPlugin.php`

The `AuthPlugin.php` file is a custom plugin that adds token-based authentication to your WebSocket server. Below is a detailed explanation of its key parts:

```php
class AuthPlugin implements PluginInterface, MiddlewareInterface
{
    protected $secretKey;
    protected $keyName;

    // 1. Constructor
    public function __construct($secretKey, $keyName = 'access_token')
    {
        $this->secretKey = $secretKey;
        $this->keyName = $keyName;
    }
```
**Explanation**: The constructor initializes the plugin with a secret key and a token parameter name. This setup allows you to easily configure which token to validate and how to validate it.

```php
    // 2. Registering the Plugin
    public function register(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener('middleware.register', [$this, 'onRegisterMiddleware']);
        $dispatcher->addListener('connection.open', [$this, 'onOpen']);
    }
```
**Explanation**: The `register` method hooks the plugin into the WebSocket server by listening for specific events like `middleware.register` and `connection.open`. This is where the authentication logic gets integrated into the server's lifecycle.

```php
    // 3. Register Middleware
    public function onRegisterMiddleware(MiddlewarePipeline $pipeline)
    {
        $pipeline->add($this);
    }
```
**Explanation**: The `onRegisterMiddleware` method adds the authentication middleware to the pipeline. This ensures that each message passing through the server is checked for authentication.

```php
    // 4. Connection Open Handler
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
```
**Explanation**: The `onOpen` method is invoked when a new WebSocket connection is opened. It extracts the token from the connection URI and validates it. If the token is invalid, the connection is closed immediately.

```php
    // 5. Message Handler Middleware
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
```
**Explanation**: The `handle` method processes incoming WebSocket messages. It checks the token within the message metadata, ensuring that only authenticated users can interact with the server.

```php
    // 6. Token Validation Method
    protected function validateToken($token)
    {
        if (!$token) {
            return false;
        }

        return $token == $this->secretKey;
    }
}
```
**Explanation**: The `validateToken` method is a utility function that compares the provided token with the secret key. This simple method can be expanded for more complex validation logic if needed.

### Code Overview for ChatWithAuth Example

- **`ChatController.php`**: Handles room join and message publishing logic.
- **`AuthPlugin.php`**: Middleware for token-based authentication.
- **`ChatService.php`**: Manages room subscriptions and broadcasts messages.
- **`index.php`**: Entry point, initializes the WebSocket server and registers the `AuthPlugin`.
- **`routes.yaml`**: Defines the routes for joining and messaging within rooms.

## API Documentation

### 1. **SocketServer**

The `SocketServer` class is the entry point for your WebSocket server.

- **run()**
  
  Starts the WebSocket server.
  
  ```php
  public function run()
  ```

- **registerEventListener(string $eventName, callable $listener)**
  
  Registers an event listener.
  
  ```php
  public function registerEventListener(string $eventName, callable $listener)
  ```

### 2. **RoutingMiddleware**

The `RoutingMiddleware` class handles routing of incoming WebSocket messages.

- **handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)**
  
  Routes the message to the appropriate controller.
  
  ```php
  public function handle(ConnectionInterface $conn, PacketInterface $packet, callable $next)
  ```

### 3. **MiddlewarePipeline**

Processes messages through a series of middleware.

- **add(MiddlewareInterface $middleware)**
  
  Adds a middleware to the pipeline.
  
  ```php
  public function add(MiddlewareInterface $middleware)
  ```

- **process(ConnectionInterface $conn, PacketInterface $packet, callable $finalHandler)**
  
  Processes the packet through the middleware pipeline.
  
  ```php
  public function process(ConnectionInterface $conn, PacketInterface $packet, callable $finalHandler)
  ```

### 4. **ConnectionManager**

Manages WebSocket connections and their subscriptions.

- **add(ConnectionInterface $conn)**
  
  Adds a new connection.
  
  ```php
  public function add(ConnectionInterface $conn)
  ```

- **remove(ConnectionInterface $conn)**
  
  Removes a connection.
  
  ```php
  public function remove(ConnectionInterface $conn)
  ```

### 5. **EventDispatcher**

Manages event listeners and dispatching events.

- **addListener(string $eventName, callable $listener)**
  
  Registers an event listener.
  
  ```php
  public function addListener(string $eventName, callable $listener)
  ```

- **dispatch(string $eventName, $eventData = null)**
  
  Dispatches an event to registered listeners.
  
  ```php
  public function dispatch(string $eventName, $eventData = null)
  ```

### 6. **Packet**

Represents a WebSocket message packet.

- **getRoute()**
  
  Returns the route of the packet.
  
  ```php
  public function getRoute(): string
  ```

- **getMessage()**
  
  Returns the message content.
  
  ```php
  public function getMessage()
  ```

- **getMetadata(string $key = null)**
  
  Returns metadata or specific key value.
  
  ```php
  public function getMetadata(string $key = null)
  ```

### 7. **PacketFactory**

Creates `Packet` instances from JSON strings.

- **createFromJson(string $json): PacketInterface**

  Creates a `Packet` from a JSON string.

  ```php
  public function createFromJson(string $json): PacketInterface
  ```

### 8. **PluginManager**

Manages the lifecycle of plugins in the system.

- **addPlugin(PluginInterface $plugin)**
  
  Adds a plugin to the manager.
  
  ```php
  public function addPlugin(PluginInterface $plugin)
  ```

- **registerPlugins(EventDispatcherInterface $dispatcher)**
  
  Registers all plugins with the event dispatcher and dispatches `plugin.registered` event.
  
  ```php
  public function registerPlugins(EventDispatcherInterface $dispatcher)
  ```

- **bootPlugins()**
  
  Boots all registered plugins.
  
  ```php
  public function bootPlugins()
  ```

#### Creating a Plugin

To create a plugin in UranusSocketServer, you need to implement the `PluginInterface`. A plugin can be used to add custom functionality to the server, such as authentication, logging, or other middleware. Here's a basic outline of how to create and register a plugin:

1. **Implement the `PluginInterface`:**

  ```php
  use Mita\UranusSocketServer\Events\EventDispatcherInterface;
  use Mita\UranusSocketServer\Plugins\PluginInterface;

  class MyCustomPlugin implements PluginInterface
  {
      public function register(EventDispatcherInterface $dispatcher)
      {
          // Register events or middleware here
      }

      public function boot()
      {
          // Code to run when the plugin is booted
      }

      public function unregister(EventDispatcherInterface $dispatcher)
      {
          // Unregister events or middleware here
      }
  }
  ```

2. **Add the Plugin to the Server:**
  ```php
  $myPlugin = new MyCustomPlugin();
  $socketServer->addPlugin($myPlugin);
  ```

By following this pattern, you can extend the functionality of your WebSocket server to fit your specific needs.

## Event Documentation

### Overview

The UranusSocketServer provides a robust event-driven architecture, allowing you to hook into various lifecycle events of the WebSocket server. This section documents the events that are available, the context in which they are triggered, the parameters they provide, and examples of how to use them.

### 1. **`connection.opened`**
   - **Description:** Dispatched when a new WebSocket connection is established.
   - **Context:** Triggered in `WebSocketService::onOpen`.
   - **Parameters:**
     - **`ConnectionInterface $conn`**: Represents the new connection.
   - **Example:**
     ```php
     $socketServer->registerEventListener('connection.opened', function($conn) {
         echo "New connection opened with ID: " . $conn->resourceId . "\n";
     });
     ```

### 2. **`message.received`**
   - **Description:** Dispatched when a WebSocket message is received.
   - **Context:** Triggered in `WebSocketService::onMessage`.
   - **Parameters:**
     - **`ConnectionInterface $connection`**: The connection instance that received the message.
     - **`string $message`**: The actual message content.
   - **Example:**
     ```php
     $socketServer->registerEventListener('message.received', function($data) {
         $conn = $data['connection'];
         $msg = $data['message'];
         echo "Message received from connection {$conn->resourceId}: {$msg}\n";
     });
     ```

### 3. **`plugin.registered`**
   - **Description:** Dispatched when a plugin is registered within the system.
   - **Context:** Triggered in `PluginManager::registerPlugins`.
   - **Parameters:**
     - **`PluginInterface $plugin`**: The registered plugin instance.
   - **Example:**
     ```php
     $socketServer->registerEventListener('plugin.registered', function($plugin) {
         echo "Plugin registered: " . get_class($plugin) . "\n";
     });
     ```

### 4. **`connection.added`**
   - **Description:** Dispatched when a WebSocket connection is added to the ConnectionManager.
   - **Context:** Triggered in `ConnectionManager::add`.
   - **Parameters:**
     - **`ConnectionInterface $conn`**: The connection object that was added.
   - **Example:**
     ```php
     $socketServer->registerEventListener('connection.added', function($conn) {
         echo "Connection added with ID: " . $conn->resourceId . "\n";
     });
     ```

### 5. **`connection.removed`**
   - **Description:** Dispatched when a WebSocket connection is removed from the ConnectionManager.
   - **Context:** Triggered in `ConnectionManager::remove`.
   - **Parameters:**
     - **`ConnectionInterface $conn`**: The connection object that was removed.
   - **Example:**
     ```php
     $socketServer->registerEventListener('connection.removed', function($conn) {
         echo "Connection removed with ID: " . $conn->resourceId . "\n";
     });
     ```

### 6. **`connection.subscribed`**
   - **Description:** Dispatched when a connection subscribes to a specific route.
   - **Context:** Triggered in `ConnectionManager::subscribe`.
   - **Parameters:**
     - **`ConnectionInterface $conn`**: The connection that subscribed.
     - **`string $route`**: The route that the connection subscribed to.
   - **Example:**
     ```php
     $socketServer->registerEventListener('connection.subscribed', function($data) {
         $conn = $data['conn'];
         $route = $data['route'];
         echo "Connection {$conn->resourceId} subscribed to route: {$route}\n";
     });
     ```

### 7. **`connection.unsubscribed`**
   - **Description:** Dispatched when a connection unsubscribes from a specific route.
   - **Context:** Triggered in `ConnectionManager::unsubscribe`.
   - **Parameters:**
     - **`ConnectionInterface $conn`**: The connection that unsubscribed.
     - **`string $route`**: The route from which the connection unsubscribed.
   - **Example:**
     ```php
     $socketServer->registerEventListener('connection.unsubscribed', function($data) {
         $conn = $data['conn'];
         $route = $data['route'];
         echo "Connection {$conn->resourceId} unsubscribed from route: {$route}\n";
     });
     ```

### 8. **`message.sent`**
   - **Description:** Dispatched when a message is sent to all subscribers of a specific route.
   - **Context:** Triggered in `ConnectionManager::sendToRoute`.
   - **Parameters:**
     - **`string $route`**: The route to which the message was sent.
     - **`string $message`**: The content of the message that was sent.
   - **Example:**
     ```php
     $socketServer->registerEventListener('message.sent', function($data) {
         $route = $data['route'];
         $message = $data['message'];
         echo "Message sent to route {$route}: {$message}\n";
     });
     ```


Thank you for using **UranusSocketServer**! We hope it helps you build your next WebSocket application with ease. If you have any questions or need support, feel free to open an issue or reach out to the community.

Happy coding!