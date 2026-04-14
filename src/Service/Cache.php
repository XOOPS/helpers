<?php

declare(strict_types=1);

/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright    2000-2026 XOOPS Project (https://xoops.org/)
 * @license      GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author       XOOPS Development Team
 */

namespace Xoops\Helpers\Service;

use Xoops\Helpers\Contracts\CacheInterface;
use Xoops\Helpers\Provider\XoopsCacheAdapter;

/**
 * Static facade for cache operations.
 *
 * Zero-config: automatically selects the best available backend
 * (XoopsCache -> APCu -> file).
 *
 * Usage:
 *   Cache::set('key', $value, 3600);
 *   $value = Cache::get('key');
 *   Cache::forget('key');
 */
final class Cache
{
    private static ?CacheInterface $adapter = null;

    /**
     * Inject a custom cache adapter.
     */
    public static function use(CacheInterface $adapter): void
    {
        self::$adapter = $adapter;
    }

    /**
     * Reset to auto-detected adapter.
     */
    public static function reset(): void
    {
        self::$adapter = null;
    }

    public static function get(string $key): mixed
    {
        return self::adapter()->get($key);
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return self::adapter()->set($key, $value, $ttl);
    }

    public static function forget(string $key): bool
    {
        return self::adapter()->forget($key);
    }

    public static function flush(): bool
    {
        return self::adapter()->flush();
    }

    public static function has(string $key): bool
    {
        return self::adapter()->has($key);
    }

    /**
     * Get a value from cache, or compute and store it.
     *
     * @param string   $key      Cache key
     * @param int      $ttl      Time to live in seconds
     * @param callable $callback Callback to compute value on cache miss
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        // Single backend lookup — avoid has()+get() double read
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        // Distinguish stored-null from miss only when needed
        if (self::has($key)) {
            return null;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * @param array<string> $keys
     * @return array<string, mixed>
     */
    public static function many(array $keys, mixed $default = null): array
    {
        return self::adapter()->many($keys, $default);
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function putMany(array $values, int $ttl = 3600): bool
    {
        return self::adapter()->putMany($values, $ttl);
    }

    private static function adapter(): CacheInterface
    {
        return self::$adapter ??= new XoopsCacheAdapter();
    }
}
