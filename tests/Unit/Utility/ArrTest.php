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
use Xoops\Helpers\Utility\Arr;
use Xoops\Helpers\Utility\MissingValue;

final class ArrTest extends TestCase
{
    // ── get() ──────────────────────────────────────────────

    public function testGetReturnsValueByKey(): void
    {
        $array = ['name' => 'XOOPS', 'version' => '2.6'];
        self::assertSame('XOOPS', Arr::get($array, 'name'));
    }

    public function testGetReturnsDotNotatedValue(): void
    {
        $array = ['app' => ['name' => 'XOOPS', 'env' => 'production']];
        self::assertSame('XOOPS', Arr::get($array, 'app.name'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        self::assertSame('fallback', Arr::get([], 'missing', 'fallback'));
    }

    public function testGetResolvesClosureDefault(): void
    {
        $result = Arr::get([], 'missing', fn() => 'computed');
        self::assertSame('computed', $result);
    }

    public function testGetReturnsNullKeyTarget(): void
    {
        $array = ['a' => 1];
        self::assertSame($array, Arr::get($array, null));
    }

    public function testGetFromObject(): void
    {
        $obj = (object) ['name' => 'test'];
        self::assertSame('test', Arr::get($obj, 'name'));
    }

    // ── has() ──────────────────────────────────────────────

    public function testHasReturnsTrueForExistingKey(): void
    {
        self::assertTrue(Arr::has(['a' => 1], 'a'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse(Arr::has(['a' => 1], 'b'));
    }

    public function testHasSupportsDotNotation(): void
    {
        $array = ['a' => ['b' => 1]];
        self::assertTrue(Arr::has($array, 'a.b'));
        self::assertFalse(Arr::has($array, 'a.c'));
    }

    public function testHasChecksMultipleKeys(): void
    {
        $array = ['a' => 1, 'b' => 2];
        self::assertTrue(Arr::has($array, ['a', 'b']));
        self::assertFalse(Arr::has($array, ['a', 'c']));
    }

    // ── set() ──────────────────────────────────────────────

    public function testSetWithSimpleKey(): void
    {
        $array = [];
        Arr::set($array, 'name', 'XOOPS');
        self::assertSame(['name' => 'XOOPS'], $array);
    }

    public function testSetWithDotNotation(): void
    {
        $array = [];
        Arr::set($array, 'app.name', 'XOOPS');
        self::assertSame(['app' => ['name' => 'XOOPS']], $array);
    }

    // ── forget() ───────────────────────────────────────────

    public function testForgetRemovesKey(): void
    {
        $array = ['a' => 1, 'b' => 2];
        Arr::forget($array, 'a');
        self::assertSame(['b' => 2], $array);
    }

    public function testForgetWithDotNotation(): void
    {
        $array = ['a' => ['b' => 1, 'c' => 2]];
        Arr::forget($array, 'a.b');
        self::assertSame(['a' => ['c' => 2]], $array);
    }

    // ── pluck() ────────────────────────────────────────────

    public function testPluckExtractsValues(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        self::assertSame(['Alice', 'Bob'], Arr::pluck($data, 'name'));
    }

    public function testPluckWithKeyColumn(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        self::assertSame([1 => 'Alice', 2 => 'Bob'], Arr::pluck($data, 'name', 'id'));
    }

    // ── only() / except() ──────────────────────────────────

    public function testOnlyReturnsSubset(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertSame(['a' => 1, 'c' => 3], Arr::only($array, ['a', 'c']));
    }

    public function testExceptRemovesKeys(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertSame(['b' => 2], Arr::except($array, ['a', 'c']));
    }

    // ── flatten() ──────────────────────────────────────────

    public function testFlatten(): void
    {
        $array = [1, [2, 3], [4, [5, 6]]];
        self::assertSame([1, 2, 3, 4, 5, 6], Arr::flatten($array));
    }

    public function testFlattenWithDepth(): void
    {
        $array = [1, [2, [3, [4]]]];
        self::assertSame([1, 2, [3, [4]]], Arr::flatten($array, 1));
    }

    // ── sortBy() / groupBy() ──────────────────────────────

    public function testSortByKey(): void
    {
        $data = [
            ['name' => 'Charlie', 'age' => 30],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 28],
        ];

        Arr::sortBy($data, 'name');
        $names = array_values(Arr::pluck($data, 'name'));
        self::assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function testGroupByKey(): void
    {
        $data = [
            ['status' => 'active', 'name' => 'Alice'],
            ['status' => 'inactive', 'name' => 'Bob'],
            ['status' => 'active', 'name' => 'Charlie'],
        ];

        $grouped = Arr::groupBy($data, 'status');
        self::assertCount(2, $grouped['active']);
        self::assertCount(1, $grouped['inactive']);
    }

    // ── dot() / undot() ────────────────────────────────────

    public function testDotFlattensKeys(): void
    {
        $array = ['app' => ['name' => 'XOOPS', 'env' => 'prod']];
        self::assertSame(['app.name' => 'XOOPS', 'app.env' => 'prod'], Arr::dot($array));
    }

    public function testUndotExpandsKeys(): void
    {
        $array = ['app.name' => 'XOOPS', 'app.env' => 'prod'];
        self::assertSame(['app' => ['name' => 'XOOPS', 'env' => 'prod']], Arr::undot($array));
    }

    // ── first() / last() ──────────────────────────────────

    public function testFirstReturnsFirstElement(): void
    {
        self::assertSame(1, Arr::first([1, 2, 3]));
    }

    public function testFirstWithCallback(): void
    {
        $result = Arr::first([1, 2, 3, 4], fn($v) => $v > 2);
        self::assertSame(3, $result);
    }

    public function testFirstReturnsDefaultWhenEmpty(): void
    {
        self::assertSame('default', Arr::first([], default: 'default'));
    }

    public function testLast(): void
    {
        self::assertSame(3, Arr::last([1, 2, 3]));
    }

    // ── where() ────────────────────────────────────────────

    public function testWhereFilters(): void
    {
        $data = [
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Charlie', 'age' => 35],
        ];

        $result = Arr::where($data, 'age', '>', 28);
        self::assertCount(2, $result);
    }

    // ── wrap() / isAssoc() / collapse() ────────────────────

    public function testWrap(): void
    {
        self::assertSame([1], Arr::wrap(1));
        self::assertSame([1, 2], Arr::wrap([1, 2]));
        self::assertSame([], Arr::wrap(null));
    }

    public function testIsAssoc(): void
    {
        self::assertTrue(Arr::isAssoc(['a' => 1, 'b' => 2]));
        self::assertFalse(Arr::isAssoc([1, 2, 3]));
        self::assertFalse(Arr::isAssoc([]));
    }

    public function testCollapse(): void
    {
        self::assertSame([1, 2, 3, 4], Arr::collapse([[1, 2], [3, 4]]));
    }
}
