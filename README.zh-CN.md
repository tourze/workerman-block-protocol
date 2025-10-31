# Workerman Block Protocol

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/php-monorepo.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/php-monorepo)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/tourze/php-monorepo.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/php-monorepo)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![License](https://img.shields.io/github/license/tourze/php-monorepo.svg?style=flat-square)](https://github.com/tourze/php-monorepo/blob/master/LICENSE)

基于 Workerman 框架的模块化协议处理库，采用"积木式"组合方式处理复杂的协议数据解析和编码。

## 目录

- [特性](#特性)
- [系统要求](#系统要求)
- [安装](#安装)
- [快速开始](#快速开始)
  - [基础用法](#基础用法)
- [可用处理器](#可用处理器)
  - [Ascii 处理器](#ascii-处理器)
  - [UnpackData 处理器](#unpackdata-处理器)
  - [Length 处理器](#length-处理器)
  - [JSON 处理器](#json-处理器)
  - [Base64 处理器](#base64-处理器)
  - [Compression 处理器](#compression-处理器)
  - [Response 处理器](#response-处理器)
- [高级用法](#高级用法)
  - [创建自定义处理器](#创建自定义处理器)
  - [复杂协议示例](#复杂协议示例)
- [测试](#测试)
- [贡献](#贡献)
- [更新日志](#更新日志)
  - [v1.0.0](#v100)
- [许可证](#许可证)

## 特性

- 🔧 **模块化设计**：组合多个处理器处理复杂协议
- 🚀 **高性能**：完全兼容 Workerman 的协议接口
- 📦 **可扩展**：轻松创建适应特定需求的自定义处理器
- 🔄 **双向支持**：同时支持编码和解码操作
- 🛡️ **类型安全**：基于 PHP 8.1+ 特性和严格类型构建
- 📊 **功能完善**：处理常见的数据处理场景：
  - ASCII 字符验证
  - 固定长度数据解析
  - JSON 数据处理
  - Base64 编码/解码
  - 数据压缩/解压
  - 长度前缀消息
  - 自定义响应生成

## 系统要求

- PHP 8.1 或更高版本
- Workerman 5.1 或更高版本
- ext-zlib 扩展

## 安装

```bash
composer require tourze/workerman-block-protocol
```

## 快速开始

### 基础用法

```php
<?php

use Workerman\Worker;
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\Ascii;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Handler\JSON;

// 创建一个Worker实例
$worker = new Worker('BlockProtocol://0.0.0.0:8080');

// 配置处理器回调
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        // 处理第一个字节作为命令类型（只允许值 1, 2, 3）
        new Ascii($connection, [1, 2, 3]),
        // 处理后续4字节长度头和对应长度的数据
        new Length($connection, 'N', 4, 65536, 'body'),
        // 将消息体解析为JSON
        new JSON($connection, 65536, 'jsonData')
    ];
};

// 处理连接事件
$worker->onMessage = function ($connection, $data) {
    // 通过连接对象访问解析结果
    $command = BlockProtocol::getPart($connection, Ascii::class);
    $jsonData = $connection->jsonData;
    
    // 根据命令处理业务逻辑
    switch ($command) {
        case 1:
            // 处理命令 1
            break;
        case 2:
            // 处理命令 2
            break;
        case 3:
            // 处理命令 3
            break;
    }
    
    // 向客户端发送响应
    $connection->send(['status' => 'ok', 'data' => $jsonData]);
};

Worker::runAll();
```

## 可用处理器

### Ascii 处理器

验证和处理单个 ASCII 字节，可选择值限制。

```php
// 只允许特定 ASCII 值
new Ascii($connection, [65, 66, 67]) // 只允许 'A', 'B', 'C'

// 允许任何 ASCII 值
new Ascii($connection)
```

### UnpackData 处理器

处理固定长度的二进制数据并可选择解包。

```php
// 4字节网络字节序整数
new UnpackData($connection, 4, 'N')

// 8字节小端双精度浮点数
new UnpackData($connection, 8, 'e')
```

### Length 处理器

处理带长度前缀的消息，支持各种整数格式。

```php
// 4字节网络字节序长度前缀，存储在 'data' 属性中
new Length($connection, 'N', 4, 65536, 'data')

// 2字节小端长度前缀，存储在 'message' 属性中
new Length($connection, 'v', 2, 1024, 'message')
```

### JSON 处理器

解析和验证 JSON 数据，支持可配置选项。

```php
// 解析为关联数组，最大 65536 字节
new JSON($connection, 65536, 'jsonData', true)

// 解析为对象，最大 1024 字节
new JSON($connection, 1024, 'jsonObject', false)
```

### Base64 处理器

处理 Base64 编码和解码，支持标准和 URL 安全变体。

```php
// 标准 Base64
new Base64($connection, false, false)

// URL 安全的 Base64
new Base64($connection, false, true)
```

### Compression 处理器

使用各种算法压缩和解压数据。

```php
// GZIP 压缩，级别 6
new Compression($connection, Compression::ALGORITHM_GZIP, 6)

// Deflate 压缩，级别 9
new Compression($connection, Compression::ALGORITHM_DEFLATE, 9)
```

### Response 处理器

自动向客户端发送响应。

```php
// 立即发送 JSON 响应
new Response($connection, '{"status":"ok"}', true)

// 存储响应以便手动发送
new Response($connection, 'Hello World', false)
```

## 高级用法

### 创建自定义处理器

通过继承 `Part` 类创建自定义处理器：

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
        // 时间戳至少需要 4 字节
        if (strlen($buffer) < 4) {
            return 0; // 需要更多数据
        }
        
        $timestamp = unpack('N', substr($buffer, 0, 4))[1];
        $this->value = $timestamp;
        
        return Part::FLAG_CONTINUE; // 继续下一个处理器
    }

    public function decode(string $buffer): string
    {
        // 将时间戳转换为可读格式
        return date('Y-m-d H:i:s', $this->value);
    }

    public function encode(mixed $data): string
    {
        // 将当前时间戳转换为二进制
        return pack('N', time());
    }
}
```

### 复杂协议示例

```php
// 处理包含多种数据类型的复杂协议
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        // 协议版本（1 字节）
        new ASCII($connection, [1, 2]),
        // 消息类型（1 字节）
        new ASCII($connection, [10, 20, 30]),
        // 时间戳（4 字节）
        new TimestampHandler($connection),
        // 压缩的 JSON 负载
        new Length($connection, 'N', 4, 1048576, 'payload'),
        new Compression($connection, Compression::ALGORITHM_GZIP),
        new JSON($connection, 1048576, 'data', true),
        // 自动响应
        new Response($connection, '{"ack": true}', true)
    ];
};
```

## 测试

```bash
# 运行测试
composer test

# 运行覆盖率测试
composer test -- --coverage-text

# 运行 PHPStan 分析
composer phpstan
```

## 贡献

我们欢迎贡献！请遵循以下指南：

1. Fork 仓库
2. 创建功能分支 (`git checkout -b feature/amazing-feature`)
3. 进行更改并编写相应测试
4. 运行测试套件 (`composer test`)
5. 运行静态分析 (`composer phpstan`)
6. 提交更改 (`git commit -am 'Add amazing feature'`)
7. 推送到分支 (`git push origin feature/amazing-feature`)
8. 开启 Pull Request

## 更新日志

### v1.0.0
- 初始版本发布，包含核心处理器
- 支持 ASCII、Length、JSON、Base64、Compression 处理器
- 完全兼容 Workerman 协议
- 全面的测试覆盖

## 许可证

MIT 许可证。详情请查看 [License File](LICENSE) 文件。
