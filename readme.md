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

### Basic Usage Example

Here’s how to set up a basic WebSocket server using **UranusSocketServer**:

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

### Running the Chat Application Example

To get a feel for how UranusSocketServer works, you can try out the included chat application example.

1. **Clone the Repository:**

   ```bash
   git clone https://github.com/mita9497dev/uranus-socket-server.git
   cd uranus-socket-server
   ```

2. **Install Dependencies:**

   ```bash
   composer install
   ```

3. **Navigate to the Example Directory:**

   ```bash
   cd examples/chat
   ```

4. **Run the WebSocket Server:**

   ```bash
   php index.php
   ```

5. **Connect to the Server:**

   Use a WebSocket client to connect to `ws://127.0.0.1:7654`. You can then join a room and send messages using JSON payloads:

   **Join a room:**

   ```json
   {
       "route": "/room/123/join",
       "msg": "Joining room 123"
   }
   ```

   **Send a message to the room:**

   ```json
   {
       "route": "/room/123/publish",
       "msg": "Hello, everyone!"
   }
   ```

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

Thank you for using **UranusSocketServer**! We hope it helps you build your next WebSocket application with ease. If you have any questions or need support, feel free to open an issue or reach out to the community.

Happy coding!