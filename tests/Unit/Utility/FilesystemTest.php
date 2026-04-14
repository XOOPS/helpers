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
use Xoops\Helpers\Utility\Filesystem;

final class FilesystemTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/xoops_fs_test_' . uniqid();
        mkdir($this->tmp, 0775, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmp)) {
            Filesystem::deleteDirectory($this->tmp);
        }
    }

    // ── JSON ────────────────────────────────────────────────

    public function testReadJsonReturnsArrayOnSuccess(): void
    {
        $path = $this->tmp . '/config.json';
        file_put_contents($path, '{"key":"value","count":3}');

        $result = Filesystem::readJson($path);

        self::assertIsArray($result);
        self::assertSame('value', $result['key']);
        self::assertSame(3, $result['count']);
    }

    public function testReadJsonReturnsNullOnMissingFile(): void
    {
        self::assertNull(Filesystem::readJson($this->tmp . '/nonexistent.json'));
    }

    public function testReadJsonReturnsNullOnInvalidJson(): void
    {
        $path = $this->tmp . '/bad.json';
        file_put_contents($path, '{not valid json}');

        self::assertNull(Filesystem::readJson($path));
    }

    public function testReadJsonReturnsNullOnJsonArray(): void
    {
        // readJson expects an object (associative array), not a bare JSON array
        $path = $this->tmp . '/array.json';
        file_put_contents($path, '[1, 2, 3]');

        self::assertNull(Filesystem::readJson($path));
    }

    public function testPutJsonWritesFile(): void
    {
        $path = $this->tmp . '/out.json';
        $result = Filesystem::putJson($path, ['a' => 1, 'b' => 'hello']);

        self::assertTrue($result);
        self::assertFileExists($path);
        self::assertStringContainsString('"a": 1', file_get_contents($path));
    }

    public function testPutJsonAndReadJsonRoundTrip(): void
    {
        $path = $this->tmp . '/roundtrip.json';
        $data = ['name' => 'news', 'version' => '2.5', 'active' => true];

        Filesystem::putJson($path, $data);
        $loaded = Filesystem::readJson($path);

        self::assertSame($data, $loaded);
    }

    // ── Directory operations ────────────────────────────────

    public function testMkdirCreatesDirectory(): void
    {
        $dir = $this->tmp . '/new/nested/dir';
        $result = Filesystem::mkdir($dir);

        self::assertTrue($result);
        self::assertDirectoryExists($dir);
    }

    public function testMkdirReturnsTrueIfAlreadyExists(): void
    {
        self::assertTrue(Filesystem::mkdir($this->tmp));
    }

    public function testDeleteDirectoryRemovesTreeRecursively(): void
    {
        $dir = $this->tmp . '/tree';
        mkdir($dir . '/sub', 0775, true);
        file_put_contents($dir . '/file.txt', 'content');
        file_put_contents($dir . '/sub/nested.txt', 'nested');

        $result = Filesystem::deleteDirectory($dir);

        self::assertTrue($result);
        self::assertDirectoryDoesNotExist($dir);
    }

    public function testDeleteDirectoryReturnsFalseIfNotDir(): void
    {
        self::assertFalse(Filesystem::deleteDirectory($this->tmp . '/nonexistent'));
    }

    public function testCopyDirectoryCopiesAllFiles(): void
    {
        $src = $this->tmp . '/source';
        $dst = $this->tmp . '/destination';
        mkdir($src . '/sub', 0775, true);
        file_put_contents($src . '/a.txt', 'AAA');
        file_put_contents($src . '/sub/b.txt', 'BBB');

        $result = Filesystem::copyDirectory($src, $dst);

        self::assertTrue($result);
        self::assertFileExists($dst . '/a.txt');
        self::assertFileExists($dst . '/sub/b.txt');
        self::assertSame('AAA', file_get_contents($dst . '/a.txt'));
        self::assertSame('BBB', file_get_contents($dst . '/sub/b.txt'));
    }

    public function testCopyDirectoryReturnsFalseIfSourceMissing(): void
    {
        self::assertFalse(Filesystem::copyDirectory($this->tmp . '/nosource', $this->tmp . '/dst'));
    }

    // ── File inspection ─────────────────────────────────────

    public function testExtensionExtractsLowercaseExtension(): void
    {
        self::assertSame('jpg', Filesystem::extension('photo.JPG'));
        self::assertSame('php', Filesystem::extension('/path/to/file.PHP'));
        self::assertSame('', Filesystem::extension('noextension'));
    }

    public function testIsImageReturnsTrueForImageExtensions(): void
    {
        foreach (['photo.jpg', 'img.jpeg', 'anim.gif', 'icon.png', 'bg.webp', 'photo.avif', 'logo.svg'] as $name) {
            self::assertTrue(Filesystem::isImage($name), "Expected isImage to be true for {$name}");
        }
    }

    public function testIsImageReturnsFalseForNonImageExtension(): void
    {
        self::assertFalse(Filesystem::isImage('document.pdf'));
        self::assertFalse(Filesystem::isImage('script.php'));
        self::assertFalse(Filesystem::isImage('archive.zip'));
    }

    public function testSizeReturnsFileSizeInBytes(): void
    {
        $path = $this->tmp . '/sized.txt';
        file_put_contents($path, 'Hello');

        self::assertSame(5, Filesystem::size($path));
    }

    public function testSizeReturnsFalseForMissingFile(): void
    {
        self::assertFalse(Filesystem::size($this->tmp . '/missing.txt'));
    }

    public function testIsWritableRecursiveReturnsTrueForWritableDir(): void
    {
        $dir = $this->tmp . '/writable';
        mkdir($dir);
        file_put_contents($dir . '/file.txt', 'x');

        self::assertTrue(Filesystem::isWritableRecursive($dir));
    }

    public function testIsWritableRecursiveReturnsFalseForNonDir(): void
    {
        self::assertFalse(Filesystem::isWritableRecursive($this->tmp . '/notadir'));
    }

    // ── Chunked reading ─────────────────────────────────────

    public function testReadChunkedCallsCallbackForEachChunk(): void
    {
        $path = $this->tmp . '/chunked.txt';
        file_put_contents($path, 'ABCDEFGH');

        $chunks = [];
        Filesystem::readChunked($path, 4, function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        self::assertSame(['ABCD', 'EFGH'], $chunks);
    }

    public function testReadChunkedReturnsFalseOnMissingFile(): void
    {
        $result = Filesystem::readChunked($this->tmp . '/missing.bin', 512, fn() => null);
        self::assertFalse($result);
    }

    public function testReadChunkedReturnsFalseOnInvalidChunkSize(): void
    {
        $path = $this->tmp . '/data.txt';
        file_put_contents($path, 'data');

        self::assertFalse(Filesystem::readChunked($path, 0, fn() => null));
    }

    public function testReadChunkedStopsWhenCallbackReturnsFalse(): void
    {
        $path = $this->tmp . '/stopper.txt';
        file_put_contents($path, str_repeat('X', 1024));

        $callCount = 0;
        Filesystem::readChunked($path, 256, function () use (&$callCount): bool {
            $callCount++;
            return false; // stop after first chunk
        });

        self::assertSame(1, $callCount);
    }

    // ── Zip / Unzip ─────────────────────────────────────────

    public function testZipAndUnzipRoundTrip(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        $src = $this->tmp . '/to_zip';
        mkdir($src . '/sub', 0775, true);
        file_put_contents($src . '/hello.txt', 'Hello');
        file_put_contents($src . '/sub/world.txt', 'World');

        $zipPath = $this->tmp . '/archive.zip';
        $result = Filesystem::zip($src, $zipPath);
        self::assertTrue($result);
        self::assertFileExists($zipPath);

        $dst = $this->tmp . '/unzipped';
        $result = Filesystem::unzip($zipPath, $dst);
        self::assertTrue($result);
        self::assertFileExists($dst . '/hello.txt');
        self::assertFileExists($dst . '/sub/world.txt');
        self::assertSame('Hello', file_get_contents($dst . '/hello.txt'));
    }

    public function testUnzipReturnsFalseOnMissingZip(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        self::assertFalse(Filesystem::unzip($this->tmp . '/missing.zip', $this->tmp . '/out'));
    }

    public function testUnzipRejectZipSlipEntry(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        // Build a zip that contains a path-traversal entry
        $zipPath = $this->tmp . '/evil.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('../escape.txt', 'malicious');
        $zip->close();

        $result = Filesystem::unzip($zipPath, $this->tmp . '/safe_dest');

        self::assertFalse($result, 'Zip Slip entry should be rejected');
    }

    public function testZipReturnsFalseOnMissingDirectory(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        self::assertFalse(Filesystem::zip($this->tmp . '/nosuchdir', $this->tmp . '/out.zip'));
    }
}
