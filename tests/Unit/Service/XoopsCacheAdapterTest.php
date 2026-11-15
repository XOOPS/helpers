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
        require_once dirname(__DIR__, 2) . '/fixtures/XoopsCache.php';

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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNativeBranchReturnsValuesWithTheirTypes(): void
    {
        require_once dirname(__DIR__, 2) . '/fixtures/XoopsCache.php';

        $cache = new XoopsCacheAdapter();
        $value = ['n' => 5, 'f' => 1.5, 'b' => true, 'off' => false, 'nil' => null, 'path' => 'C:\dir\file'];

        self::assertTrue($cache->set('typed', $value));
        self::assertSame($value, $cache->get('typed'));
        self::assertTrue($cache->has('typed'));

        self::assertTrue($cache->set('int', 0));
        self::assertSame(0, $cache->get('int'));
        self::assertTrue($cache->set('false', false));
        self::assertFalse($cache->get('false'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNativeBranchTreatsForeignOrDamagedEntriesAsMisses(): void
    {
        require_once dirname(__DIR__, 2) . '/fixtures/XoopsCache.php';

        $cache = new XoopsCacheAdapter();

        // The array shape written by 1.1.1 and earlier.
        \XoopsCache::$store['xmf_old'] = ['__xmf_hit' => true, 'value' => 'v'];
        \XoopsCache::$store['xmf_other'] = 'plain string';
        \XoopsCache::$store['xmf_damaged'] = 'xmf1:%%%';
        \XoopsCache::$store['xmf_garbled'] = 'xmf1:' . base64_encode('a:1:{i:0;s:9:"short";}');
        // unserialize() warns but still returns PHP_INT_MAX here, on every supported PHP.
        \XoopsCache::$store['xmf_overflow'] = 'xmf1:' . base64_encode('i:999999999999999999999999;');
        \XoopsCache::$store['xmf_empty'] = 'xmf1:';
        \XoopsCache::$store['xmf_object'] = 'xmf1:' . base64_encode(serialize(new \ArrayObject([1])));

        self::assertNull($cache->get('old'));
        self::assertNull($cache->get('other'));
        self::assertNull($cache->get('damaged'));
        self::assertNull($cache->get('garbled'));
        self::assertNull($cache->get('overflow'));
        self::assertNull($cache->get('empty'));
        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $cache->get('object'));
    }
}
