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

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Provider\DefaultUrlGenerator;

final class DefaultUrlGeneratorTest extends TestCase
{
    // ── H3: XOOPS_UPLOAD_URL honored ────────────────────────
    //
    // XOOPS_UPLOAD_URL is not defined by the shared bootstrap (and constants
    // cannot be undefined once set), so the honored-constant branch is tested
    // in an isolated child process where defining it cannot leak into the
    // fallback assertions performed elsewhere in the suite.

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUploadHonorsXoopsUploadUrl(): void
    {
        define('XOOPS_UPLOAD_URL', 'http://x.test/uploads');

        $result = (new DefaultUrlGenerator())->upload('quotes/author/p.png');

        self::assertSame('http://x.test/uploads/quotes/author/p.png', $result);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testModuleUploadHonorsXoopsUploadUrl(): void
    {
        define('XOOPS_UPLOAD_URL', 'http://x.test/uploads');

        $generator = new DefaultUrlGenerator();

        self::assertSame(
            $generator->upload('quotes/author/p.png'),
            $generator->moduleUpload('quotes', 'author/p.png'),
        );
        self::assertSame(
            'http://x.test/uploads/quotes/author/p.png',
            $generator->moduleUpload('quotes', 'author/p.png'),
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUploadTrailingSlashOnConstantIsNormalised(): void
    {
        define('XOOPS_UPLOAD_URL', 'http://x.test/uploads/');

        $result = (new DefaultUrlGenerator())->upload('p.png');

        self::assertSame('http://x.test/uploads/p.png', $result);
    }

    // ── H3: fallback when XOOPS_UPLOAD_URL is undefined ──────
    // XOOPS_URL is defined as 'http://localhost' by the bootstrap.

    public function testUploadFallbackIsSiteRooted(): void
    {
        $result = (new DefaultUrlGenerator())->upload('x.png');

        self::assertSame('http://localhost/uploads/x.png', $result);
    }

    public function testModuleUploadFallbackIsSiteRooted(): void
    {
        $result = (new DefaultUrlGenerator())->moduleUpload('quotes', 'author/p.png');

        self::assertSame('http://localhost/uploads/quotes/author/p.png', $result);
    }
}
