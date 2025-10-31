<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

use Tourze\Workerman\BlockProtocol\Exception\InvalidProtocolArgumentException;
use Workerman\Connection\ConnectionInterface;

/**
 * 压缩处理器
 * 支持各种压缩算法对数据进行压缩和解压
 */
class Compression extends Part
{
    public const ALGORITHM_GZIP = 'gzip';
    public const ALGORITHM_DEFLATE = 'deflate';
    public const ALGORITHM_ZLIB = 'zlib';

    /**
     * @param ConnectionInterface $connection 连接对象
     * @param string              $algorithm  压缩算法
     * @param int                 $level      压缩级别，1-9，9为最高压缩比
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $algorithm = self::ALGORITHM_GZIP,
        private readonly int $level = 6,
    ) {
        parent::__construct($connection);

        // 检查支持的压缩算法
        if (!in_array($algorithm, [self::ALGORITHM_GZIP, self::ALGORITHM_DEFLATE, self::ALGORITHM_ZLIB], true)) {
            throw new InvalidProtocolArgumentException("不支持的压缩算法: {$algorithm}");
        }

        // 检查压缩级别范围
        if ($level < 1 || $level > 9) {
            throw new InvalidProtocolArgumentException('压缩级别必须在1-9之间');
        }
    }

    public function input(string $buffer): int
    {
        // 压缩处理器不做任何输入处理，仅在编码解码时工作
        return self::FLAG_CONTINUE;
    }

    public function decode(string $buffer): string
    {
        // 如果缓冲区为空，则直接返回
        if ('' === $buffer) {
            return $buffer;
        }

        // 设置错误处理器，用于捕获警告
        $errorHandler = static function ($severity, $message, $file, $line) {
            // 静默处理压缩/解压警告
            return true;
        };

        $previousHandler = set_error_handler($errorHandler);

        try {
            // 根据选择的算法解压数据
            switch ($this->algorithm) {
                case self::ALGORITHM_GZIP:
                    $decompressed = gzdecode($buffer);
                    break;
                case self::ALGORITHM_DEFLATE:
                    $decompressed = gzinflate($buffer);
                    break;
                case self::ALGORITHM_ZLIB:
                    $decompressed = gzuncompress($buffer);
                    break;
                default:
                    return $buffer;
            }
        } finally {
            // 恢复之前的错误处理器
            restore_error_handler();
        }

        // 如果解压失败，则返回原始数据
        return false !== $decompressed ? $decompressed : $buffer;
    }

    public function encode(string $buffer): string
    {
        // 如果缓冲区为空，则直接返回
        if ('' === $buffer) {
            return $buffer;
        }

        // 根据选择的算法压缩数据
        switch ($this->algorithm) {
            case self::ALGORITHM_GZIP:
                $result = gzencode($buffer, $this->level);
                if (false === $result) {
                    throw new InvalidProtocolArgumentException('Failed to compress data using gzip');
                }

                return $result;
            case self::ALGORITHM_DEFLATE:
                $result = gzdeflate($buffer, $this->level);
                if (false === $result) {
                    throw new InvalidProtocolArgumentException('Failed to compress data using deflate');
                }

                return $result;
            case self::ALGORITHM_ZLIB:
                $result = gzcompress($buffer, $this->level);
                if (false === $result) {
                    throw new InvalidProtocolArgumentException('Failed to compress data using zlib');
                }

                return $result;
            default:
                return $buffer;
        }
    }
}
