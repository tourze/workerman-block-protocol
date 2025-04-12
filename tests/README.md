# Workerman Block Protocol 测试说明

## 测试概述

本项目包含以下几类测试：

1. `BlockProtocolTest`: 测试协议框架的基本功能
2. `Handler/*Test`: 测试各种处理器的功能

## 测试辅助类

### MockConnection

`MockConnection` 类是 `AsyncTcpConnection` 的一个模拟实现，用于在不需要实际网络连接的情况下测试协议。它提供了以下功能：

- 模拟连接的创建（自动提供默认参数）
- 模拟数据发送和连接关闭
- 记录发送的数据和连接状态
- 提供状态重置功能

## 测试要点

### 协议功能测试

- 处理器初始化和配置
- 数据输入和处理
- 编码和解码功能
- 处理器链式处理

### 处理器测试

- ASCII处理器：处理单字节ASCII码
- Base64处理器：Base64编码和解码
- Compression处理器：数据压缩
- JSON处理器：JSON格式处理
- Length处理器：带长度前缀的消息
- Response处理器：自动响应
- UnpackData处理器：二进制数据解包

## 已知问题

在某些情况下，`MockConnection` 与 `AsyncTcpConnection` 之间存在兼容性问题，可能导致测试失败。这些问题主要与Workerman框架本身的实现有关，如在处理器类型验证和析构时可能出现问题。对于这类问题，可使用 `markTestSkipped` 暂时跳过相关测试。

## 测试注意事项

1. 运行测试前确保已安装所有依赖
2. 使用PHPUnit运行测试: `./vendor/bin/phpunit packages/workerman-block-protocol/tests`
3. 当遇到与Workerman框架兼容性相关的问题时，可考虑：
   - 修改测试以适应Workerman的行为
   - 跳过特定测试
   - 创建更好的模拟对象

## 后续改进

- 添加更多边界条件测试
- 提高测试覆盖率
- 改进MockConnection以更好地模拟Workerman的行为
- 添加负载测试和性能测试
