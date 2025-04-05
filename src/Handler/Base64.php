<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Workerman\Connection\ConnectionInterface;

/**
 * Base64编码处理器
 * 用于数据的Base64编码和解码
 */
class Base64 extends Part
{
    /**
     * @param ConnectionInterface $connection 连接对象
     * @param bool $strict 是否使用严格模式解码
     * @param bool $urlSafe 是否使用URL安全的base64编码
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly bool $strict = false,
        private readonly bool $urlSafe = false
    ) {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // Base64处理器不处理输入，仅在编码解码阶段工作
        return static::FLAG_CONTINUE;
    }

    public function decode(string $buffer): string
    {
        if (empty($buffer)) {
            return '';
        }

        $data = $buffer;

        // 如果是URL安全的base64，先转换回标准base64
        if ($this->urlSafe) {
            $data = str_replace(['-', '_'], ['+', '/'], $data);
        }

        // 使用指定模式解码
        $decoded = base64_decode($data, $this->strict);

        // 解码失败时返回原始数据
        return $decoded !== false ? $decoded : $buffer;
    }

    public function encode(string $buffer): string
    {
        if (empty($buffer)) {
            return '';
        }

        // 先进行标准base64编码
        $encoded = base64_encode($buffer);

        // 如果需要URL安全版本，替换特殊字符
        if ($this->urlSafe) {
            $encoded = str_replace(['+', '/'], ['-', '_'], $encoded);
            // 移除padding填充字符
            $encoded = rtrim($encoded, '=');
        }

        return $encoded;
    }
}
