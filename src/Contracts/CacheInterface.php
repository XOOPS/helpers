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

namespace Xoops\Helpers\Contracts;

/**
 * Simple cache contract for XOOPS helpers.
 *
 * Inspired by PSR-16 (SimpleCache) but tailored for XOOPS.
 * Implementations can use XoopsCache, APCu, file-based cache,
 * or in-memory arrays for testing.
 */
interface CacheInterface
{
    /**
     * Retrieve an item from the cache.
     *
     * @param string $key Cache key
     * @return mixed Cached value or null if not found/expired
     */
    public function get(string $key): mixed;

    /**
     * Store an item in the cache.
     *
     * @param string $key   Cache key
     * @param mixed  $value Value to cache
     * @param int    $ttl   Time to live in seconds (0 = forever)
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool;

    /**
     * Check if an item exists and is not expired.
     */
    public function has(string $key): bool;

    /**
     * Retrieve multiple items at once.
     *
     * @param array<string> $keys    Cache keys
     * @param mixed         $default Default value for missing keys
     * @return array<string, mixed>
     */
    public function many(array $keys, mixed $default = null): array;

    /**
     * Store multiple items at once.
     *
     * @param array<string, mixed> $values Key-value pairs
     * @param int                  $ttl    Time to live in seconds
     */
    public function putMany(array $values, int $ttl = 3600): bool;
}
