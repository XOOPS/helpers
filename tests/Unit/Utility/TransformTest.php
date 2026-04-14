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
use Xoops\Helpers\Utility\Transform;

final class TransformTest extends TestCase
{
    // ── transform() ─────────────────────────────────────────

    public function testTransformAppliesCallbackWhenFilled(): void
    {
        $result = Transform::transform('hello', fn(string $v) => strtoupper($v));
        self::assertSame('HELLO', $result);
    }

    public function testTransformReturnsNullDefaultWhenBlank(): void
    {
        $result = Transform::transform('', fn(string $v) => strtoupper($v));
        self::assertNull($result);
    }

    public function testTransformReturnsNullDefaultWhenNull(): void
    {
        $result = Transform::transform(null, fn($v) => 'transformed');
        self::assertNull($result);
    }

    public function testTransformReturnsScalarDefaultWhenBlank(): void
    {
        $result = Transform::transform('', fn(string $v) => strtoupper($v), 'fallback');
        self::assertSame('fallback', $result);
    }

    public function testTransformResolvesClosureDefaultWhenBlank(): void
    {
        $result = Transform::transform(null, fn($v) => 'transformed', fn() => 'from_closure');
        self::assertSame('from_closure', $result);
    }

    public function testTransformWithWhitespaceOnlyIsBlank(): void
    {
        $result = Transform::transform('   ', fn(string $v) => 'applied', 'default');
        self::assertSame('default', $result);
    }

    public function testTransformWithZeroIsNotBlank(): void
    {
        // Numeric zero is filled, not blank
        $result = Transform::transform(0, fn(int $v) => $v + 10);
        self::assertSame(10, $result);
    }

    public function testTransformWithFalseIsNotBlank(): void
    {
        // Boolean false is filled, not blank
        $result = Transform::transform(false, fn(bool $v) => !$v);
        self::assertTrue($result);
    }

    // ── when() ──────────────────────────────────────────────

    public function testWhenAppliesCallbackWhenPredicateReturnsTrue(): void
    {
        $result = Transform::when(5, fn(int $v) => $v > 3, fn(int $v) => $v * 2);
        self::assertSame(10, $result);
    }

    public function testWhenReturnsElseWhenPredicateReturnsFalse(): void
    {
        $result = Transform::when(1, fn(int $v) => $v > 3, fn(int $v) => $v * 2, 'small');
        self::assertSame('small', $result);
    }

    public function testWhenReturnsNullElseByDefault(): void
    {
        $result = Transform::when(1, fn(int $v) => $v > 3, fn(int $v) => $v * 2);
        self::assertNull($result);
    }

    public function testWhenResolvesClosureElse(): void
    {
        $result = Transform::when(
            1,
            fn(int $v) => $v > 100,
            fn(int $v) => 'large',
            fn() => 'computed_small',
        );

        self::assertSame('computed_small', $result);
    }

    public function testWhenWithStringPredicate(): void
    {
        $result = Transform::when(
            'hello@example.com',
            fn(string $v) => str_contains($v, '@'),
            fn(string $v) => 'valid_email',
            'invalid_email',
        );

        self::assertSame('valid_email', $result);
    }
}
