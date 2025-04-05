# Workerman Block Protocol

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-block-protocol.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-block-protocol)
[![License](https://img.shields.io/github/license/tourze/workerman-block-protocol.svg?style=flat-square)](https://github.com/tourze/workerman-block-protocol/blob/master/LICENSE)

基于 Workerman 框架的模块化协议处理库，采用"积木式"组合方式处理协议数据。

## 特性

- 完全兼容 Workerman 的协议接口
- 支持多种处理器组合使用
- 可扩展性强，便于添加自定义处理器
- 支持常见数据处理需求：
  - ASCII字符
  - 定长数据
  - JSON数据
  - Base64编码/解码
  - 数据压缩

## 安装

```bash
composer require tourze/workerman-block-protocol
```

## 快速开始

### 配置处理器链

```php
use Workerman\Worker;
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\ASCII;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Handler\JSON;

// 创建一个Worker实例
$worker = new Worker('BlockProtocol://0.0.0.0:8080');

// 配置处理器回调
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        // 处理第一个字节作为命令类型
        new ASCII($connection, [1, 2, 3]),
        // 处理后续4字节长度头和对应长度的数据
        new Length($connection, 'N', 4, 65536, 'body'),
        // 将消息体解析为JSON
        new JSON($connection, 65536, 'jsonData')
    ];
};

// 处理连接事件
$worker->onMessage = function ($connection, $data) {
    // 处理完成后，可以通过连接对象访问解析结果
    $command = BlockProtocol::getPart($connection, ASCII::class);
    $jsonData = $connection->jsonData;

    // 处理业务逻辑...
};

Worker::runAll();
```

## 可用处理器

该库提供了多种内置处理器：

### ASCII

处理单个字节的ASCII码。

```php
new ASCII($connection, [65, 66, 67]) // 只允许'A', 'B', 'C'
```

### UnpackData

处理固定长度的数据并可选择解包。

```php
new UnpackData($connection, 4, 'N') // 4字节网络字节序整数
```

### Length

处理带长度前缀的消息。

```php
new Length($connection, 'N', 4, 65536, 'data') // 4字节网络字节序长度前缀
```

### JSON

处理JSON格式数据。

```php
new JSON($connection, 65536, 'jsonData', true) // 最大65536字节，解析为关联数组
```

### Base64

处理Base64编码/解码。

```php
new Base64($connection, false, true) // URL安全的Base64
```

### Compression

处理数据压缩/解压。

```php
new Compression($connection, Compression::ALGORITHM_GZIP, 6) // GZIP压缩，级别6
```

### Response

向客户端发送固定响应。

```php
new Response($connection, '{"status":"ok"}', true) // 自动发送响应
```

## 高级用法

### 创建自定义处理器

你可以通过继承 `Part` 类来创建自定义处理器：

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
        // 实现输入处理逻辑
        // 返回值：
        // -1: Part::FLAG_CONTINUE，表示已处理，继续下一个处理器
        // 0: 需要更多数据
        // >0: 已处理的数据长度
    }

    public function decode(string $buffer): string
    {
        // 实现解码逻辑
    }

    public function encode(string $buffer): string
    {
        // 实现编码逻辑
    }
}
```

### 同时使用压缩和编码

```php
use Tourze\Workerman\BlockProtocol\BlockProtocol;
use Tourze\Workerman\BlockProtocol\Handler\Length;
use Tourze\Workerman\BlockProtocol\Handler\Compression;
use Tourze\Workerman\BlockProtocol\Handler\Base64;

// 配置处理器链，支持数据压缩和Base64编码
BlockProtocol::$handlerCallback = function ($connection) {
    return [
        new Length($connection),
        new Compression($connection, Compression::ALGORITHM_GZIP),
        new Base64($connection)
    ];
};
```

## 贡献

欢迎贡献！请随时提交 Pull Request。

## 许可证

MIT 许可证。详情请查看 [License File](LICENSE) 文件。
