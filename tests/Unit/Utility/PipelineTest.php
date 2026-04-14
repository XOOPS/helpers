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
use Xoops\Helpers\Utility\Pipeline;

final class PipelineTest extends TestCase
{
    public function testSendAndThenReturn(): void
    {
        $result = Pipeline::send(10)
            ->pipe(fn($v) => $v + 5)
            ->pipe(fn($v) => $v * 2)
            ->thenReturn();

        self::assertSame(30, $result);
    }

    public function testThrough(): void
    {
        $result = Pipeline::send('  Hello World  ')
            ->through([
                fn($v) => trim($v),
                fn($v) => strtolower($v),
                fn($v) => str_replace(' ', '-', $v),
            ])
            ->thenReturn();

        self::assertSame('hello-world', $result);
    }

    public function testThen(): void
    {
        $result = Pipeline::send(5)
            ->pipe(fn($v) => $v * 3)
            ->then(fn($v) => "Result: {$v}");

        self::assertSame('Result: 15', $result);
    }

    public function testEmptyPipelineReturnsOriginal(): void
    {
        $result = Pipeline::send('unchanged')->thenReturn();
        self::assertSame('unchanged', $result);
    }
}
