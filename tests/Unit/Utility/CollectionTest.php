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
use Xoops\Helpers\Utility\Collection;

final class CollectionTest extends TestCase
{
    public function testMakeFromArray(): void
    {
        $c = Collection::make([1, 2, 3]);
        self::assertSame([1, 2, 3], $c->all());
    }

    public function testMakeWrapsScalar(): void
    {
        $c = Collection::make(42);
        self::assertSame([42], $c->all());
    }

    public function testMap(): void
    {
        $c = Collection::make([1, 2, 3])->map(fn($v) => $v * 2);
        self::assertSame([2, 4, 6], $c->all());
    }

    public function testFilter(): void
    {
        $c = Collection::make([1, 2, 3, 4])->filter(fn($v) => $v % 2 === 0);
        self::assertSame([1 => 2, 3 => 4], $c->all());
    }

    public function testReject(): void
    {
        $c = Collection::make([1, 2, 3, 4])->reject(fn($v) => $v % 2 === 0);
        self::assertSame([0 => 1, 2 => 3], $c->all());
    }

    public function testReduce(): void
    {
        $sum = Collection::make([1, 2, 3])->reduce(fn($carry, $v) => $carry + $v, 0);
        self::assertSame(6, $sum);
    }

    public function testPluck(): void
    {
        $c = Collection::make([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        self::assertSame(['Alice', 'Bob'], $c->pluck('name')->all());
    }

    public function testGroupBy(): void
    {
        $c = Collection::make([
            ['status' => 'active', 'name' => 'A'],
            ['status' => 'inactive', 'name' => 'B'],
            ['status' => 'active', 'name' => 'C'],
        ]);

        $grouped = $c->groupBy('status');
        self::assertCount(2, $grouped->get('active'));
    }

    public function testSortBy(): void
    {
        $c = Collection::make([
            ['name' => 'Charlie'],
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ]);

        $sorted = $c->sortBy('name')->values();
        self::assertSame('Alice', $sorted->first()['name']);
    }

    public function testFirstAndLast(): void
    {
        $c = Collection::make([1, 2, 3]);
        self::assertSame(1, $c->first());
        self::assertSame(3, $c->last());
    }

    public function testFirstWithCallback(): void
    {
        $c = Collection::make([1, 2, 3, 4]);
        self::assertSame(3, $c->first(fn($v) => $v > 2));
    }

    public function testChunk(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5])->chunk(2);
        self::assertSame(3, $c->count());
    }

    public function testTakeAndSkip(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5]);
        self::assertSame([1, 2, 3], $c->take(3)->all());
        self::assertSame([3, 4, 5], $c->skip(2)->all());
    }

    public function testSumAvgMinMax(): void
    {
        $c = Collection::make([10, 20, 30]);
        self::assertSame(60, $c->sum());
        self::assertSame(20, $c->avg());
        self::assertSame(10, $c->min());
        self::assertSame(30, $c->max());
    }

    public function testIsEmptyAndNotEmpty(): void
    {
        self::assertTrue(Collection::make([])->isEmpty());
        self::assertTrue(Collection::make([1])->isNotEmpty());
    }

    public function testCountable(): void
    {
        self::assertCount(3, Collection::make([1, 2, 3]));
    }

    public function testIterable(): void
    {
        $items = [];
        foreach (Collection::make([1, 2, 3]) as $item) {
            $items[] = $item;
        }
        self::assertSame([1, 2, 3], $items);
    }

    public function testJsonSerializable(): void
    {
        $json = json_encode(Collection::make(['a' => 1, 'b' => 2]));
        self::assertSame('{"a":1,"b":2}', $json);
    }

    public function testPipe(): void
    {
        $result = Collection::make([1, 2, 3])->pipe(fn($c) => $c->sum());
        self::assertSame(6, $result);
    }

    public function testWhen(): void
    {
        $c = Collection::make([1, 2, 3])
            ->when(true, fn($c) => $c->map(fn($v) => $v * 10));

        self::assertSame([10, 20, 30], $c->all());
    }

    public function testRange(): void
    {
        $c = Collection::range(1, 5);
        self::assertSame([1, 2, 3, 4, 5], $c->all());
    }

    public function testUnique(): void
    {
        $c = Collection::make([1, 2, 2, 3, 3, 3])->unique()->values();
        self::assertSame([1, 2, 3], $c->all());
    }

    public function testFlatten(): void
    {
        $c = Collection::make([[1, 2], [3, [4, 5]]])->flatten();
        self::assertSame([1, 2, 3, 4, 5], $c->all());
    }
}
