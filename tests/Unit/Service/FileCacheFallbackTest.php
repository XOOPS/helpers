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
use Xoops\Helpers\Utility\Filesystem;

/**
 * XoopsCacheAdapter's temp-directory file fallback. The bootstrap defines
 * XOOPS_VAR_PATH, so each case runs tests/fixtures/file-cache.php in a child
 * process whose temp directory points at a scratch dir.
 */
final class FileCacheFallbackTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = XOOPS_ROOT_PATH . '/cache_fallback_' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($this->tmp, 0700, true));
    }

    protected function tearDown(): void
    {
        Filesystem::deleteDirectory($this->tmp);
    }

    public function testSeparateInstancesShareAndOverwriteEntries(): void
    {
        if (DIRECTORY_SEPARATOR === '/' && !function_exists('posix_geteuid')) {
            self::markTestSkipped('the file cache is disabled on POSIX without ext-posix');
        }

        self::assertSame(
            [true, 'one', true, 'two', true, null],
            $this->runOps([['set', 'k', 'one'], ['get', 'k'], ['set', 'k', 'two'], ['get', 'k'], ['forget', 'k'], ['get', 'k']]),
        );
    }

    public function testPlantedEntrySymlinkIsReplacedNotWrittenThrough(): void
    {
        $dir = $this->posixCacheDir();
        self::assertTrue(mkdir($dir, 0700));
        $sentinel = $this->tmp . '/sentinel.txt';
        file_put_contents($sentinel, 'untouched');
        $entry = $dir . '/' . hash('sha256', 'xmf_k') . '.cache';
        if (!@symlink($sentinel, $entry)) {
            self::markTestSkipped('symlinks not supported in this environment');
        }

        self::assertSame([true, 'v'], $this->runOps([['set', 'k', 'v'], ['get', 'k']]));
        self::assertSame('untouched', file_get_contents($sentinel));
        self::assertFalse(is_link($entry));
    }

    public function testLinkedCacheDirectoryIsRefused(): void
    {
        $dir = $this->posixCacheDir();
        $elsewhere = $this->tmp . '/elsewhere';
        self::assertTrue(mkdir($elsewhere, 0777));
        if (!@symlink($elsewhere, $dir)) {
            self::markTestSkipped('symlinks not supported in this environment');
        }

        self::assertSame([false, null], $this->runOps([['set', 'k', 'v'], ['get', 'k']]));
        self::assertSame([], glob($elsewhere . '/*'));
    }

    public function testFlushLeavesRefusedDirectoryUntouched(): void
    {
        $dir = $this->posixCacheDir();
        $elsewhere = $this->tmp . '/elsewhere';
        self::assertTrue(mkdir($elsewhere, 0777));
        $victim = $elsewhere . '/keep.cache';
        self::assertNotFalse(file_put_contents($victim, 'keep'));
        if (!@symlink($elsewhere, $dir)) {
            self::markTestSkipped('symlinks not supported in this environment');
        }

        self::assertSame([true], $this->runOps([['flush']]));
        self::assertFileExists($victim);
    }

    public function testOwnCacheDirectoryOpenToOthersIsRefused(): void
    {
        $dir = $this->posixCacheDir();
        self::assertTrue(mkdir($dir));
        self::assertTrue(chmod($dir, 0770));

        self::assertSame([false, null], $this->runOps([['set', 'k', 'v'], ['get', 'k']]));
        self::assertSame([], glob($dir . '/*'));
    }

    public function testCacheDirectoryOwnedByAnotherAccountIsRefused(): void
    {
        $dir = $this->posixCacheDir();
        if (posix_geteuid() !== 0) {
            self::markTestSkipped('needs root to hand the directory to another account');
        }
        self::assertTrue(mkdir($dir, 0777));
        if (!@chown($dir, 65534)) {
            self::markTestSkipped('could not hand the directory to another account');
        }

        self::assertSame([false, null], $this->runOps([['set', 'k', 'v'], ['get', 'k']]));
        self::assertSame([], glob($dir . '/*'));
    }

    private function posixCacheDir(): string
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('posix_geteuid')) {
            self::markTestSkipped('POSIX-only: per-user temp cache directory');
        }

        return $this->tmp . '/xmf_cache_' . posix_geteuid();
    }

    /**
     * @param list<array{0: string, 1?: string, 2?: string}> $ops
     * @return list<mixed>
     */
    private function runOps(array $ops): array
    {
        $env = getenv() + ['TMPDIR' => '', 'TMP' => '', 'TEMP' => ''];
        $env['TMPDIR'] = $env['TMP'] = $env['TEMP'] = $this->tmp;

        $process = proc_open(
            [PHP_BINARY, '-d', 'apc.enable_cli=0', dirname(__DIR__, 2) . '/fixtures/file-cache.php', json_encode($ops)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env,
        );
        self::assertIsResource($process);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        $exit = proc_close($process);

        self::assertSame(0, $exit, $err . $out);

        return json_decode((string) $out, true, 8, JSON_THROW_ON_ERROR);
    }
}
