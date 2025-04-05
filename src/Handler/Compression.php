<?php

namespace Tourze\Workerman\BlockProtocol\Handler;

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
     * @param string $algorithm 压缩算法
     * @param int $level 压缩级别，1-9，9为最高压缩比
     */
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $algorithm = self::ALGORITHM_GZIP,
        private readonly int $level = 6
    ) {
        parent::__construct($connection);

        // 检查支持的压缩算法
        if (!in_array($algorithm, [self::ALGORITHM_GZIP, self::ALGORITHM_DEFLATE, self::ALGORITHM_ZLIB])) {
            throw new \InvalidArgumentException("不支持的压缩算法: {$algorithm}");
        }

        // 检查压缩级别范围
        if ($level < 1 || $level > 9) {
            throw new \InvalidArgumentException("压缩级别必须在1-9之间");
        }
    }

    /**
     * @inheritDoc
     */
    public function input(string $buffer): int
    {
        // 压缩处理器不做任何输入处理，仅在编码解码时工作
        return static::FLAG_CONTINUE;
    }

    /**
     * @inheritDoc
     */
    public function decode(string $buffer): string
    {
        // 如果缓冲区为空，则直接返回
        if (empty($buffer)) {
            return $buffer;
        }

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

        // 如果解压失败，则返回原始数据
        return $decompressed !== false ? $decompressed : $buffer;
    }

    /**
     * @inheritDoc
     */
    public function encode(string $buffer): string
    {
        // 如果缓冲区为空，则直接返回
        if (empty($buffer)) {
            return $buffer;
        }

        // 根据选择的算法压缩数据
        switch ($this->algorithm) {
            case self::ALGORITHM_GZIP:
                return gzencode($buffer, $this->level);
            case self::ALGORITHM_DEFLATE:
                return gzdeflate($buffer, $this->level);
            case self::ALGORITHM_ZLIB:
                return gzcompress($buffer, $this->level);
            default:
                return $buffer;
        }
    }
}
