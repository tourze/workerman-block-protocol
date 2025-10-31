# Workerman Block Protocol

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/php-monorepo.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/php-monorepo)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/tourze/php-monorepo.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/php-monorepo)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![License](https://img.shields.io/github/license/tourze/php-monorepo.svg?style=flat-square)](https://github.com/tourze/php-monorepo/blob/master/LICENSE)

A modular protocol processing library for Workerman framework, using a "building blocks" approach to handle complex protocol data parsing and encoding.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [Basic Usage](#basic-usage)
- [Available Handlers](#available-handlers)
  - [Ascii Handler](#ascii-handler)
  - [UnpackData Handler](#unpackdata-handler)
  - [Length Handler](#length-handler)
  - [JSON Handler](#json-handler)
  - [Base64 Handler](#base64-handler)
  - [Compression Handler](#compression-handler)
  - [Response Handler](#response-handler)
- [Advanced Usage](#advanced-usage)
  - [Creating Custom Handlers](#creating-custom-handlers)
  - [Complex Protocol Example](#complex-protocol-example)
- [Testing](#testing)
- [Contributing](#contributing)
- [Changelog](#changelog)
  - [v1.0.0](#v100)
- [License](#license)

## Features

- 🔧 **Modular Design**: Combine multiple handlers to process complex protocols
- 🚀 **High Performance**: Fully compatible with Workerman's protocol interface
- 📦 **Extensible**: Easy to create custom handlers for specific needs
- 🔄 **Bidirectional**: Support both encoding and decoding operations
- 🛡️ **Type Safe**: Built with PHP 8.1+ features and strict typing
- 📊 **Comprehensive**: Handles common data processing scenarios:
  - ASCII character validation
  - Fixed-length data parsing
  - JSON data processing
  - Base64 encoding/decoding
  - Data compression/decompression
  - Length-prefixed messages
  - Custom response generation

## Requirements

- PHP 8.1 or higher
- Workerman 5.1 or higher
- ext-zlib extension

## Installation

```bash
composer require tourze/workerman-block-protocol
```

## Quick Start

### Basic Usage

```php
<?php

use Workerman\Worker;
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\Ascii;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Handler\JSON;

// Create a Worker instance
$worker = new Worker('BlockProtocol://0.0.0.0:8080');

// Configure handler callback
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        // Process first byte as command type (only allow values 1, 2, 3)
        new Ascii($connection, [1, 2, 3]),
        // Process next 4 bytes as length header and corresponding data
        new Length($connection, 'N', 4, 65536, 'body'),
        // Parse message body as JSON
        new JSON($connection, 65536, 'jsonData')
    ];
};

// Handle connection events
$worker->onMessage = function ($connection, $data) {
    // Access parsed results through connection object
    $command = BlockProtocol::getPart($connection, Ascii::class);
    $jsonData = $connection->jsonData;
    
    // Process business logic based on command
    switch ($command) {
        case 1:
            // Handle command 1
            break;
        case 2:
            // Handle command 2
            break;
        case 3:
            // Handle command 3
            break;
    }
    
    // Send response back to client
    $connection->send(['status' => 'ok', 'data' => $jsonData]);
};

Worker::runAll();
```

## Available Handlers

### Ascii Handler

Validates and processes single ASCII bytes with optional value restrictions.

```php
// Only allow specific ASCII values
new Ascii($connection, [65, 66, 67]) // Only allows 'A', 'B', 'C'

// Allow any ASCII value
new Ascii($connection)
```

### UnpackData Handler

Processes fixed-length binary data with optional unpacking.

```php
// 4-byte network byte order integer
new UnpackData($connection, 4, 'N')

// 8-byte little-endian double
new UnpackData($connection, 8, 'e')
```

### Length Handler

Handles messages with length prefixes, supporting various integer formats.

```php
// 4-byte network byte order length prefix, store in 'data' property
new Length($connection, 'N', 4, 65536, 'data')

// 2-byte little-endian length prefix, store in 'message' property
new Length($connection, 'v', 2, 1024, 'message')
```

### JSON Handler

Parses and validates JSON data with configurable options.

```php
// Parse as associative array, max 65536 bytes
new JSON($connection, 65536, 'jsonData', true)

// Parse as object, max 1024 bytes
new JSON($connection, 1024, 'jsonObject', false)
```

### Base64 Handler

Handles Base64 encoding and decoding with standard and URL-safe variants.

```php
// Standard Base64
new Base64($connection, false, false)

// URL-safe Base64
new Base64($connection, false, true)
```

### Compression Handler

Compresses and decompresses data using various algorithms.

```php
// GZIP compression, level 6
new Compression($connection, Compression::ALGORITHM_GZIP, 6)

// Deflate compression, level 9
new Compression($connection, Compression::ALGORITHM_DEFLATE, 9)
```

### Response Handler

Automatically sends responses to clients.

```php
// Send JSON response immediately
new Response($connection, '{"status":"ok"}', true)

// Store response for manual sending
new Response($connection, 'Hello World', false)
```

## Advanced Usage

### Creating Custom Handlers

Extend the `Part` class to create custom handlers:

```php
use Tourze\Workerman\BlockProtocol\Handler\Part;
use Workerman\Connection\ConnectionInterface;

class TimestampHandler extends Part
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // Need at least 4 bytes for timestamp
        if (strlen($buffer) < 4) {
            return 0; // Need more data
        }
        
        $timestamp = unpack('N', substr($buffer, 0, 4))[1];
        $this->value = $timestamp;
        
        return Part::FLAG_CONTINUE; // Continue to next handler
    }

    public function decode(string $buffer): string
    {
        // Convert timestamp to readable format
        return date('Y-m-d H:i:s', $this->value);
    }

    public function encode(mixed $data): string
    {
        // Convert current timestamp to binary
        return pack('N', time());
    }
}
```

### Complex Protocol Example

```php
// Handle a complex protocol with multiple data types
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        // Protocol version (1 byte)
        new ASCII($connection, [1, 2]),
        // Message type (1 byte)  
        new ASCII($connection, [10, 20, 30]),
        // Timestamp (4 bytes)
        new TimestampHandler($connection),
        // Compressed JSON payload
        new Length($connection, 'N', 4, 1048576, 'payload'),
        new Compression($connection, Compression::ALGORITHM_GZIP),
        new JSON($connection, 1048576, 'data', true),
        // Automatic response
        new Response($connection, '{"ack": true}', true)
    ];
};
```

## Testing

```bash
# Run tests
composer test

# Run with coverage
composer test -- --coverage-text

# Run PHPStan analysis
composer phpstan
```

## Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with proper tests
4. Run the test suite (`composer test`)
5. Run static analysis (`composer phpstan`)
6. Commit your changes (`git commit -am 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## Changelog

### v1.0.0
- Initial release with core handlers
- Support for ASCII, Length, JSON, Base64, Compression handlers
- Full Workerman protocol compatibility
- Comprehensive test coverage

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
