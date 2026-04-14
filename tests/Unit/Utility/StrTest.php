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

namespace Xoops\Helpers\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Str;

final class StrTest extends TestCase
{
    public function testCamel(): void
    {
        self::assertSame('fooBar', Str::camel('foo_bar'));
        self::assertSame('fooBarBaz', Str::camel('foo-bar-baz'));
    }

    public function testSnake(): void
    {
        self::assertSame('foo_bar', Str::snake('fooBar'));
        self::assertSame('foo_bar_baz', Str::snake('FooBarBaz'));
    }

    public function testStudly(): void
    {
        self::assertSame('FooBar', Str::studly('foo_bar'));
        self::assertSame('FooBarBaz', Str::studly('foo-bar-baz'));
    }

    public function testKebab(): void
    {
        self::assertSame('foo-bar', Str::kebab('fooBar'));
    }

    public function testSlug(): void
    {
        self::assertSame('hello-world', Str::slug('Hello World'));
        self::assertSame('hello_world', Str::slug('Hello World', '_'));
    }

    public function testLimit(): void
    {
        self::assertSame('Hello...', Str::limit('Hello World', 5));
        self::assertSame('Hello World', Str::limit('Hello World', 100));
    }

    public function testRandom(): void
    {
        $random = Str::random(32);
        self::assertSame(32, strlen($random));
        self::assertNotSame($random, Str::random(32));
    }

    public function testContains(): void
    {
        self::assertTrue(Str::contains('Hello World', 'World'));
        self::assertFalse(Str::contains('Hello World', 'world'));
        self::assertTrue(Str::contains('Hello World', 'world', ignoreCase: true));
    }

    public function testStartsWith(): void
    {
        self::assertTrue(Str::startsWith('Hello World', 'Hello'));
        self::assertFalse(Str::startsWith('Hello World', 'World'));
    }

    public function testEndsWith(): void
    {
        self::assertTrue(Str::endsWith('Hello World', 'World'));
        self::assertFalse(Str::endsWith('Hello World', 'Hello'));
    }

    public function testBetween(): void
    {
        self::assertSame('bar', Str::between('foo[bar]baz', '[', ']'));
    }

    public function testReplaceFirst(): void
    {
        self::assertSame('foo baz bar', Str::replaceFirst('bar', 'baz', 'foo bar bar'));
    }

    public function testReplaceLast(): void
    {
        self::assertSame('foo bar baz', Str::replaceLast('bar', 'baz', 'foo bar bar'));
    }

    public function testLength(): void
    {
        self::assertSame(5, Str::length('Hello'));
    }

    public function testIsEmail(): void
    {
        self::assertTrue(Str::isEmail('test@example.com'));
        self::assertFalse(Str::isEmail('not-an-email'));
    }

    public function testIsUrl(): void
    {
        self::assertTrue(Str::isUrl('https://xoops.org'));
        self::assertFalse(Str::isUrl('not a url'));
    }

    public function testIsIp(): void
    {
        self::assertTrue(Str::isIp('127.0.0.1'));
        self::assertTrue(Str::isIp('::1'));
        self::assertFalse(Str::isIp('999.999.999.999'));
    }

    public function testIsJson(): void
    {
        self::assertTrue(Str::isJson('{"key":"value"}'));
        self::assertFalse(Str::isJson('{invalid}'));
    }

    public function testIsHexColor(): void
    {
        self::assertTrue(Str::isHexColor('#FF0000'));
        self::assertTrue(Str::isHexColor('#fff'));
        self::assertFalse(Str::isHexColor('red'));
    }

    public function testMask(): void
    {
        self::assertSame('He***orld', Str::mask('Helloorld', '*', 2, 3));
    }

    public function testWordCount(): void
    {
        self::assertSame(3, Str::wordCount('Hello beautiful world'));
    }
}
