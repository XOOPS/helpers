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
use Xoops\Helpers\Provider\XoopsCacheAdapter;

/**
 * XoopsCacheAdapter's native branch, taken when the core XoopsCache class is
 * loaded. A defined class cannot be removed again, so the case runs in a child
 * process.
 */
final class XoopsCacheAdapterTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNativeBranchRoutesEveryOperationThroughXoopsCache(): void
    {
        require dirname(__DIR__, 2) . '/fixtures/XoopsCache.php';

        $cache = new XoopsCacheAdapter();

        self::assertTrue($cache->set('a', 'one'));
        self::assertTrue($cache->set('b', 'two'));
        self::assertSame('one', $cache->get('a'));
        self::assertArrayHasKey('xmf_a', \XoopsCache::$store);

        self::assertTrue($cache->forget('a'));
        self::assertNull($cache->get('a'));

        self::assertTrue($cache->flush());
        self::assertSame([], \XoopsCache::$store);
        self::assertNull($cache->get('b'));
    }
}
