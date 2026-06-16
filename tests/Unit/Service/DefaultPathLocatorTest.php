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
use Xoops\Helpers\Provider\DefaultPathLocator;

final class DefaultPathLocatorTest extends TestCase
{
    // ── H1: forward-slash paths ─────────────────────────────

    public function testModulePathContainsNoBackslash(): void
    {
        $result = (new DefaultPathLocator())->modulePath('quotes', 'a/b/c.php');

        self::assertStringNotContainsString('\\', $result);
    }

    public function testModulePathEqualsForwardSlashString(): void
    {
        $expected = str_replace('\\', '/', XOOPS_ROOT_PATH) . '/modules/quotes/a/b/c.php';

        self::assertSame($expected, (new DefaultPathLocator())->modulePath('quotes', 'a/b/c.php'));
    }

    public function testModulePathNormalisesBackslashesInRelativePath(): void
    {
        // A relative path supplied with backslashes is normalised to '/'
        $result = (new DefaultPathLocator())->modulePath('quotes', 'a\\b\\c.php');

        self::assertStringNotContainsString('\\', $result);
        self::assertStringContainsString('/modules/quotes/a/b/c.php', $result);
    }

    public function testBasePathContainsNoBackslash(): void
    {
        $result = (new DefaultPathLocator())->basePath('includes/common.php');

        self::assertStringNotContainsString('\\', $result);
    }

    public function testModulePathWithoutRelativePath(): void
    {
        $expected = str_replace('\\', '/', XOOPS_ROOT_PATH) . '/modules/quotes';

        self::assertSame($expected, (new DefaultPathLocator())->modulePath('quotes'));
    }

    public function testThemePathContainsNoBackslash(): void
    {
        $result = (new DefaultPathLocator())->themePath('starter', 'css/style.css');

        self::assertStringNotContainsString('\\', $result);
        self::assertStringContainsString('/themes/starter/css/style.css', $result);
    }
}
