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
use Xoops\Helpers\Service\Config;

final class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::reset();
    }

    public function testRegisterLoaderAndGet(): void
    {
        Config::registerLoader('mymod', fn() => [
            'title' => 'My Module',
            'items_per_page' => 10,
        ]);

        self::assertSame('My Module', Config::get('mymod.title'));
        self::assertSame(10, Config::get('mymod.items_per_page'));
    }

    public function testGetReturnsDefault(): void
    {
        Config::registerLoader('empty', fn() => []);
        self::assertSame('fallback', Config::get('empty.missing', 'fallback'));
    }

    public function testHas(): void
    {
        Config::registerLoader('test', fn() => ['exists' => true]);
        self::assertTrue(Config::has('test.exists'));
        self::assertFalse(Config::has('test.missing'));
    }

    public function testSet(): void
    {
        Config::registerLoader('test', fn() => ['key' => 'original']);
        Config::set('test.key', 'updated');
        self::assertSame('updated', Config::get('test.key'));
    }

    public function testForget(): void
    {
        Config::registerLoader('test', fn() => ['a' => 1, 'b' => 2]);
        Config::get('test.a'); // trigger load
        Config::forget('test.a');
        self::assertFalse(Config::has('test.a'));
        self::assertTrue(Config::has('test.b'));
    }

    public function testAll(): void
    {
        Config::registerLoader('test', fn() => ['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], Config::all('test'));
    }

    public function testReload(): void
    {
        $counter = 0;
        Config::registerLoader('test', function () use (&$counter) {
            $counter++;
            return ['count' => $counter];
        });

        self::assertSame(1, Config::get('test.count'));
        Config::reload('test');
        self::assertSame(2, Config::get('test.count'));
    }

    public function testCacheIntegration(): void
    {
        $loadCount = 0;
        Config::registerLoader('cached', function () use (&$loadCount) {
            $loadCount++;
            return ['value' => 'cached'];
        });

        Config::setCache(new ArrayCache());

        self::assertSame('cached', Config::get('cached.value'));
        self::assertSame(1, $loadCount);

        Config::clear();
        self::assertSame('cached', Config::get('cached.value'));
        // Should be loaded from cache, not the loader
        self::assertSame(1, $loadCount);
    }

    public function testSystemConfigFromGlobals(): void
    {
        $GLOBALS['xoopsConfig'] = ['sitename' => 'Test XOOPS'];
        self::assertSame('Test XOOPS', Config::get('system.sitename'));
        unset($GLOBALS['xoopsConfig']);
    }
}
