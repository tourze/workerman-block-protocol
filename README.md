# Workerman Block Protocol

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![License](https://img.shields.io/github/license/tourze/workerman-block-protocol.svg?style=flat-square)](https://github.com/tourze/workerman-block-protocol/blob/master/LICENSE)

A modular protocol processing library for Workerman framework, using a "building blocks" approach to handle protocol data.

## Features

- Fully compatible with Workerman's protocol interface
- Supports combination of multiple handlers
- Highly extensible, easy to add custom handlers
- Handles common data processing needs:
  - ASCII characters
  - Fixed-length data
  - JSON data
  - Base64 encoding/decoding
  - Data compression

## Installation

```bash
composer require tourze/workerman-block-protocol
```

## Quick Start

### Configuring Handler Chain

```php
use Workerman\Worker;
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\ASCII;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Handler\JSON;

// Create a Worker instance
$worker = new Worker('BlockProtocol://0.0.0.0:8080');

// Configure handler callback
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        // Process first byte as command type
        new ASCII($connection, [1, 2, 3]),
        // Process the next 4 bytes as length header and corresponding data
        new Length($connection, 'N', 4, 65536, 'body'),
        // Parse message body as JSON
        new JSON($connection, 65536, 'jsonData')
    ];
};

// Handle connection events
$worker->onMessage = function ($connection, $data) {
    // After processing, access the parsed results through the connection object
    $command = BlockProtocol::getPart($connection, ASCII::class);
    $jsonData = $connection->jsonData;
    
    // Handle business logic...
};

Worker::runAll();
```

## Available Handlers

The library provides several built-in handlers:

### ASCII

Handles a single byte of ASCII code.

```php
new ASCII($connection, [65, 66, 67]) // Only allows 'A', 'B', 'C'
```

### UnpackData

Processes fixed-length data with optional unpacking.

```php
new UnpackData($connection, 4, 'N') // 4-byte network byte order integer
```

### Length

Processes messages with length prefix.

```php
new Length($connection, 'N', 4, 65536, 'data') // 4-byte network byte order length prefix
```

### JSON

Handles JSON format data.

```php
new JSON($connection, 65536, 'jsonData', true) // Maximum 65536 bytes, parse as associative array
```

### Base64

Handles Base64 encoding/decoding.

```php
new Base64($connection, false, true) // URL-safe Base64
```

### Compression

Handles data compression/decompression.

```php
new Compression($connection, Compression::ALGORITHM_GZIP, 6) // GZIP compression, level 6
```

### Response

Sends a fixed response to the client.

```php
new Response($connection, '{"status":"ok"}', true) // Auto-send response
```

## Advanced Usage

### Creating Custom Handlers

You can create custom handlers by extending the `Part` class:

```php
use Tourze\Workerman\BlockProtocol\Handler\Part;
use Workerman\Connection\ConnectionInterface;

class MyCustomHandler extends Part
{
    public function __construct(ConnectionInterface $connection, private string $param)
    {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // Implement input processing logic
        // Return values:
        // -1: Part::FLAG_CONTINUE, indicates processed, continue to next handler
        // 0: Need more data
        // >0: Length of processed data
    }

    public function decode(string $buffer): string
    {
        // Implement decoding logic
    }

    public function encode(string $buffer): string
    {
        // Implement encoding logic
    }
}
```

### Using Compression and Encoding Together

```php
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Handler\Compression;
use Tourze\Workerman\BlockProtocol\Handler\Base64;

// Configure handler chain with compression and Base64 encoding
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        new Length($connection),
        new Compression($connection, Compression::ALGORITHM_GZIP),
        new Base64($connection)
    ];
};
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
