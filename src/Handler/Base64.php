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
     * @param bool                $strict     是否使用严格模式解码
     * @param bool                $urlSafe    是否使用URL安全的base64编码
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly bool $strict = false,
        private readonly bool $urlSafe = false,
    ) {
        parent::__construct($connection);
    }

    public function input(string $buffer): int
    {
        // Base64处理器不处理输入，仅在编码解码阶段工作
        return self::FLAG_CONTINUE;
    }

    public function decode(string $buffer): string
    {
        if ('' === $buffer) {
            return '';
        }

        $data = $buffer;

        // 如果是URL安全的base64，先转换回标准base64
        if ($this->urlSafe) {
            $data = str_replace(['-', '_'], ['+', '/'], $data);
        }

        // 使用严格模式解码以满足静态分析要求
        // 在非严格模式下，我们仍使用严格解码但处理失败的情况
        $decoded = base64_decode($data, true);

        // 在非严格模式下，如果严格解码失败，我们清理输入并重试
        if (false === $decoded && !$this->strict) {
            // 清理非标准字符，仅保留base64有效字符
            $cleanedData = preg_replace('/[^A-Za-z0-9+\/=]/', '', $data);
            if (null !== $cleanedData && $cleanedData !== $data) {
                $decoded = base64_decode($cleanedData, true);
            }
        }

        // 解码失败时返回原始数据
        return false !== $decoded ? $decoded : $buffer;
    }

    public function encode(string $buffer): string
    {
        if ('' === $buffer) {
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
