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

namespace Xoops\Helpers\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Provider\ArrayCache;
use Xoops\Helpers\Service\Cache;

final class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::use(new ArrayCache());
    }

    protected function tearDown(): void
    {
        Cache::reset();
    }

    public function testSetAndGet(): void
    {
        Cache::set('key', 'value');
        self::assertSame('value', Cache::get('key'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        self::assertNull(Cache::get('nonexistent'));
    }

    public function testForget(): void
    {
        Cache::set('key', 'value');
        Cache::forget('key');
        self::assertNull(Cache::get('key'));
    }

    public function testHas(): void
    {
        Cache::set('key', 'value');
        self::assertTrue(Cache::has('key'));
        self::assertFalse(Cache::has('missing'));
    }

    public function testFlush(): void
    {
        Cache::set('a', 1);
        Cache::set('b', 2);
        Cache::flush();
        self::assertNull(Cache::get('a'));
        self::assertNull(Cache::get('b'));
    }

    public function testRemember(): void
    {
        $counter = 0;
        $callback = function () use (&$counter) {
            $counter++;
            return 'computed';
        };

        self::assertSame('computed', Cache::remember('key', 3600, $callback));
        self::assertSame('computed', Cache::remember('key', 3600, $callback));
        self::assertSame(1, $counter);
    }

    public function testMany(): void
    {
        Cache::set('a', 1);
        Cache::set('b', 2);
        $result = Cache::many(['a', 'b', 'c'], 'default');
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 'default'], $result);
    }

    public function testPutMany(): void
    {
        Cache::putMany(['x' => 10, 'y' => 20]);
        self::assertSame(10, Cache::get('x'));
        self::assertSame(20, Cache::get('y'));
    }
}
