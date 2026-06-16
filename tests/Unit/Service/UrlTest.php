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
use Xoops\Helpers\Service\Url;

final class UrlTest extends TestCase
{
    protected function tearDown(): void
    {
        Url::reset();
    }

    public function testToGeneratesUrl(): void
    {
        $result = Url::to('index.php');
        self::assertSame('http://localhost/index.php', $result);
    }

    public function testToWithQueryParams(): void
    {
        $result = Url::to('search.php', ['q' => 'xoops', 'page' => 1]);
        self::assertSame('http://localhost/search.php?q=xoops&page=1', $result);
    }

    public function testAsset(): void
    {
        $result = Url::asset('themes/starter/css/style.css');
        self::assertSame('http://localhost/themes/starter/css/style.css', $result);
    }

    public function testModule(): void
    {
        $result = Url::module('news', 'article.php', ['id' => 42]);
        self::assertSame('http://localhost/modules/news/article.php?id=42', $result);
    }

    public function testTheme(): void
    {
        $result = Url::theme('starter', 'css/style.css');
        self::assertSame('http://localhost/themes/starter/css/style.css', $result);
    }

    // ── H3: upload URLs (XOOPS_UPLOAD_URL undefined → site-rooted fallback) ──
    // The test bootstrap deliberately does NOT define XOOPS_UPLOAD_URL, so these
    // exercise the fallback branch. The honored-constant branch is covered in a
    // separate, process-isolated test (see DefaultUrlGeneratorTest).

    public function testUploadFallsBackToSiteRootedUploadsUrl(): void
    {
        $result = Url::upload('quotes/author/p.png');
        self::assertSame('http://localhost/uploads/quotes/author/p.png', $result);
    }

    public function testModuleUploadEqualsUploadOfCombinedPath(): void
    {
        self::assertSame(
            Url::upload('quotes/author/p.png'),
            Url::moduleUpload('quotes', 'author/p.png'),
        );
    }

    public function testModuleUploadFallbackValue(): void
    {
        $result = Url::moduleUpload('quotes', 'author/p.png');
        self::assertSame('http://localhost/uploads/quotes/author/p.png', $result);
    }
}
