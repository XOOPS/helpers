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

/**
 * In-memory stand-in for the core XoopsCache, with the same static/instance
 * split as class/cache/xoopscache.php in XOOPS 2.5 and 2.7: read(), write(),
 * delete() and getInstance() are static, clear() is an instance method.
 *
 * Not loaded by the test bootstrap: defining XoopsCache switches every
 * XoopsCacheAdapter onto its native branch, so tests require it in a separate
 * process. PHPStan scans it to check the adapter's calls against that split.
 */
class XoopsCache
{
    /** @var array<string, mixed> */
    public static array $store = [];

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function write(string $key, mixed $value, ?int $duration = null): bool
    {
        self::$store[$key] = $value;

        return true;
    }

    public static function read(string $key, ?string $config = null): mixed
    {
        return self::$store[$key] ?? false;
    }

    public static function delete(string $key, ?string $config = null): bool
    {
        unset(self::$store[$key]);

        return true;
    }

    public function clear(bool $check = false, ?string $config = null): bool
    {
        self::$store = [];

        return true;
    }
}
