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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xoops\Helpers\Utility\ThrowHelper;

final class ThrowHelperTest extends TestCase
{
    // ── throwIf ─────────────────────────────────────────────

    public function testThrowIfThrowsWhenConditionIsTrue(): void
    {
        $this->expectException(RuntimeException::class);
        ThrowHelper::throwIf(true, RuntimeException::class, 'condition was true');
    }

    public function testThrowIfDoesNotThrowWhenConditionIsFalse(): void
    {
        // Must not throw — test passes if we reach this assertion
        ThrowHelper::throwIf(false, RuntimeException::class, 'should not throw');
        self::assertTrue(true);
    }

    public function testThrowIfPassesMessageToException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must be positive');
        ThrowHelper::throwIf(-1 < 0, InvalidArgumentException::class, 'ID must be positive');
    }

    public function testThrowIfThrowsCorrectExceptionClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ThrowHelper::throwIf(true, InvalidArgumentException::class, 'wrong type');
    }

    // ── throwUnless ─────────────────────────────────────────

    public function testThrowUnlessThrowsWhenConditionIsFalse(): void
    {
        $this->expectException(RuntimeException::class);
        ThrowHelper::throwUnless(false, RuntimeException::class, 'condition was false');
    }

    public function testThrowUnlessDoesNotThrowWhenConditionIsTrue(): void
    {
        ThrowHelper::throwUnless(true, RuntimeException::class, 'should not throw');
        self::assertTrue(true);
    }

    public function testThrowUnlessPassesMessageToException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin access required');
        ThrowHelper::throwUnless(false, RuntimeException::class, 'Admin access required');
    }

    public function testThrowUnlessThrowsCorrectExceptionClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ThrowHelper::throwUnless(false, InvalidArgumentException::class, 'bad input');
    }

    // ── Guard clause pattern ─────────────────────────────────

    public function testTypicalGuardClausePattern(): void
    {
        $validate = function (int $id): void {
            ThrowHelper::throwIf($id < 1, InvalidArgumentException::class, 'ID must be positive');
            ThrowHelper::throwUnless(is_int($id), InvalidArgumentException::class, 'ID must be integer');
        };

        $this->expectException(InvalidArgumentException::class);
        $validate(-5);
    }
}
