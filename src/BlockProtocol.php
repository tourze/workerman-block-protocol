<?php

namespace Tourze\Workerman\BlockProtocol;

use Tourze\Workerman\BlockProtocol\Handler\Part;
use WeakMap;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\ProtocolInterface;

/**
 * 积木协议
 */
class BlockProtocol implements ProtocolInterface
{
    /**
     * @var WeakMap<ConnectionInterface, Part[]>
     */
    private static WeakMap $partHandlers;

    /**
     * 处理器回调函数，用于初始化连接的处理器
     * @var callable
     */
    public static $handlerCallback;

    /**
     * 静态初始化
     */
    public static function init(): void
    {
        if (!isset(self::$partHandlers)) {
            self::$partHandlers = new WeakMap();
        }
    }

    public static function initConnection(ConnectionInterface $connection): void
    {
        // 确保WeakMap已初始化
        if (!isset(self::$partHandlers)) {
            self::init();
        }

        if (!self::$partHandlers->offsetExists($connection)) {
            $callback = self::$handlerCallback;
            if ($callback) {
                self::$partHandlers[$connection] = $callback($connection);
            } else {
                self::$partHandlers[$connection] = [];
            }
        }
    }

    /**
     * 获取指定类型的Part处理器的值
     *
     * @param ConnectionInterface $connection 连接对象
     * @param string $part Part类名
     * @return mixed Part处理器的值
     */
    public static function getPart(ConnectionInterface $connection, string $part): mixed
    {
        if (!isset(self::$partHandlers)) {
            self::init();
        }

        foreach (self::$partHandlers[$connection] ?? [] as $handler) {
            /** @var Part $handler */
            if ($part === $handler::class) {
                return $handler->getValue();
            }
        }
        return null;
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        self::initConnection($connection);
        foreach (self::$partHandlers[$connection] ?? [] as $part) {
            $v = $part->input($buffer);
            // Part::FLAG_CONTINUE 代表已经处理过了
            if ($v === Part::FLAG_CONTINUE) {
                continue;
            }
            return $v;
        }

        return strlen($buffer);
    }

    public static function decode(string $buffer, ConnectionInterface $connection): string
    {
        self::initConnection($connection);
        foreach (self::$partHandlers[$connection] ?? [] as $part) {
            $buffer = $part->decode($buffer);
        }
        return $buffer;
    }

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        self::initConnection($connection);
        // 反向顺序执行encode，与decode顺序相反
        foreach (array_reverse(self::$partHandlers[$connection] ?? []) as $part) {
            /** @var Part $part */
            $data = $part->encode($data);
        }
        return $data;
    }
}

// 静态初始化
BlockProtocol::init();
