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

    /** @var list<string> */
    private array $junctions = [];

    /** @var list<string> */
    private array $mounts = [];

    protected function setUp(): void
    {
        // Under the bootstrap's private root, never a guessable name in the shared temp dir.
        $this->tmp = XOOPS_ROOT_PATH . '/fs_test_' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->tmp, 0700));
    }

    protected function tearDown(): void
    {
        // deleteDirectory() cannot unlink a junction; rmdir() removes it without its target.
        foreach ($this->junctions as $junction) {
            @rmdir($junction);
        }

        // Unmount first, or deleteDirectory() would empty the mounted source through it.
        foreach ($this->mounts as $mount) {
            exec('umount ' . escapeshellarg($mount));
        }

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

    public function testCopyDirectoryRefusesADestinationInsideTheSource(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/a.txt', 'AAA');
        mkdir($this->tmp . '/other', 0700);

        // Each spelling resolves to the source or below it; the copy would walk its own output.
        foreach (['/source', '/source/', '/source/copy/deeper', '/other/missing/../../source/copy'] as $dst) {
            self::assertFalse(Filesystem::copyDirectory($src, $this->tmp . $dst), $dst);
        }
        self::assertSame(['.', '..', 'a.txt'], scandir($src));
        self::assertDirectoryDoesNotExist($this->tmp . '/other/missing');

        // A sibling that only shares the name prefix is not inside it.
        self::assertTrue(Filesystem::copyDirectory($src, $this->tmp . '/source-copy'));
        self::assertSame('AAA', file_get_contents($this->tmp . '/source-copy/a.txt'));
    }

    public function testCopyDirectoryRefusesADestinationReachedThroughALinkIntoTheSource(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src . '/sub', 0700, true);
        $this->symlinkOrSkip($src . '/sub', $this->tmp . '/alias');

        self::assertFalse(Filesystem::copyDirectory($src, $this->tmp . '/alias/copy'));
        self::assertDirectoryDoesNotExist($src . '/sub/copy');

        // The ".." climbs out of a missing directory and back through the link.
        self::assertFalse(Filesystem::copyDirectory($src, $this->tmp . '/missing/../alias/copy'));
        self::assertDirectoryDoesNotExist($src . '/sub/copy');
        self::assertDirectoryDoesNotExist($this->tmp . '/missing');
    }

    public function testCopyDirectoryRefusesADestinationInABindMountOfASourceSubdirectory(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src . '/sub', 0700, true);
        mkdir($this->tmp . '/mount', 0700);
        $this->bindMountOrSkip($src . '/sub', $this->tmp . '/mount');

        self::assertFalse(Filesystem::copyDirectory($src, $this->tmp . '/mount/new'));
        self::assertDirectoryDoesNotExist($src . '/sub/new');
    }

    public function testCopyDirectoryRecreatesADirectoryLinkInsteadOfFollowingIt(): void
    {
        // "loop" points back at the source: following it would copy the tree into itself without end.
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/a.txt', 'AAA');
        $this->symlinkOrSkip($src, $src . '/loop');
        $dst = $this->tmp . '/destination';

        if (DIRECTORY_SEPARATOR === '\\') {
            // readlink() cannot tell a relative link here, and the copy would point into the source.
            self::assertFalse(Filesystem::copyDirectory($src, $dst));
            self::assertFalse(is_link($dst . '/loop'));

            return;
        }

        self::assertTrue(Filesystem::copyDirectory($src, $dst));
        self::assertSame('AAA', file_get_contents($dst . '/a.txt'));
        self::assertTrue(is_link($dst . '/loop'));
        self::assertSame(readlink($src . '/loop'), readlink($dst . '/loop'));

        // Copying again keeps the identical link instead of failing on it.
        self::assertTrue(Filesystem::copyDirectory($src, $dst));
    }

    public function testMoveDirectoryMovesTheTree(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src . '/sub', 0700, true);
        file_put_contents($src . '/sub/a.txt', 'AAA');

        self::assertTrue(Filesystem::moveDirectory($src, $this->tmp . '/moved'));
        self::assertSame('AAA', file_get_contents($this->tmp . '/moved/sub/a.txt'));
        self::assertDirectoryDoesNotExist($src);
    }

    public function testMoveDirectoryRefusesASourceHoldingADirectoryLink(): void
    {
        // The recreated link would point into the deleted source.
        $src = $this->tmp . '/source';
        mkdir($src . '/sub/target', 0700, true);
        file_put_contents($src . '/sub/target/a.txt', 'AAA');

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->junctionOrSkip($src . '/sub/target', $src . '/sub/link');
        } else {
            $this->symlinkOrSkip($src . '/sub/target', $src . '/sub/link');
        }

        self::assertFalse(Filesystem::moveDirectory($src, $this->tmp . '/moved'));
        self::assertDirectoryDoesNotExist($this->tmp . '/moved');
        self::assertSame('AAA', file_get_contents($src . '/sub/target/a.txt'));
    }

    public function testMoveDirectoryAcceptsARelativeSource(): void
    {
        mkdir($this->tmp . '/source/sub', 0700, true);
        file_put_contents($this->tmp . '/source/sub/a.txt', 'AAA');
        $cwd = getcwd();
        self::assertIsString($cwd);
        chdir($this->tmp);

        try {
            self::assertTrue(Filesystem::moveDirectory('./source/', 'moved'));
        } finally {
            chdir($cwd);
        }

        self::assertSame('AAA', file_get_contents($this->tmp . '/moved/sub/a.txt'));
        self::assertDirectoryDoesNotExist($this->tmp . '/source');
    }

    public function testMoveDirectoryRefusesADotEndingSource(): void
    {
        mkdir($this->tmp . '/source/sub', 0700, true);

        self::assertFalse(Filesystem::moveDirectory($this->tmp . '/source/sub/..', $this->tmp . '/moved'));
        self::assertFalse(Filesystem::moveDirectory($this->tmp . '/source/.', $this->tmp . '/moved'));
        self::assertDirectoryDoesNotExist($this->tmp . '/moved');
        self::assertDirectoryExists($this->tmp . '/source/sub');
    }

    public function testMoveDirectoryRefusesALinkedSourceRoot(): void
    {
        // Deleting through the link would empty its target.
        mkdir($this->tmp . '/target', 0700);
        file_put_contents($this->tmp . '/target/a.txt', 'AAA');

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->junctionOrSkip($this->tmp . '/target', $this->tmp . '/link');
        } else {
            $this->symlinkOrSkip($this->tmp . '/target', $this->tmp . '/link');
        }

        self::assertFalse(Filesystem::moveDirectory($this->tmp . '/link', $this->tmp . '/moved'));
        self::assertFalse(Filesystem::moveDirectory($this->tmp . '/link/', $this->tmp . '/moved'));
        self::assertDirectoryDoesNotExist($this->tmp . '/moved');
        self::assertSame('AAA', file_get_contents($this->tmp . '/target/a.txt'));
    }

    public function testMoveDirectoryRefusesASourceHoldingADanglingLink(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/a.txt', 'AAA');
        $this->symlinkOrSkip($this->tmp . '/gone', $src . '/z-dangling');

        self::assertFalse(Filesystem::moveDirectory($src, $this->tmp . '/moved'));
        self::assertDirectoryDoesNotExist($this->tmp . '/moved');
        self::assertSame('AAA', file_get_contents($src . '/a.txt'));
    }

    public function testCopyDirectoryRefusesALinkAlreadyInTheDestination(): void
    {
        // dst/a leads back to src/a/out: following it would copy src/a into itself.
        $src = $this->tmp . '/source';
        mkdir($src . '/a/out', 0700, true);
        $dst = $this->tmp . '/destination';
        mkdir($dst, 0700);
        $this->symlinkOrSkip($src . '/a/out', $dst . '/a');

        self::assertFalse(Filesystem::copyDirectory($src, $dst));
        self::assertDirectoryDoesNotExist($src . '/a/out/out');
    }

    public function testCopyDirectoryCopiesToWhereTheCheckedPathLeads(): void
    {
        // "zlink/.." is the source's parent once the link is followed, but recursive mkdir()
        // would apply the ".." lexically and create source/out, inside the source.
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/a.txt', 'AAA');
        mkdir($this->tmp . '/elsewhere/deeper', 0700, true);
        $this->symlinkOrSkip($this->tmp . '/elsewhere/deeper', $src . '/zlink');

        self::assertTrue(Filesystem::copyDirectory($src, $src . '/zlink/../out'));
        self::assertSame('AAA', file_get_contents($this->tmp . '/elsewhere/out/a.txt'));
        self::assertDirectoryDoesNotExist($src . '/out');

        // After "missing/..", "zlink/.." must still follow the link, not drop it lexically.
        self::assertTrue(Filesystem::copyDirectory($src, $src . '/missing/../zlink/../again'));
        self::assertSame('AAA', file_get_contents($this->tmp . '/elsewhere/again/a.txt'));
        self::assertDirectoryDoesNotExist($src . '/again');
        self::assertDirectoryDoesNotExist($src . '/missing');
    }

    public function testCopyDirectoryRefusesToWriteThroughAHardLinkInTheDestination(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/a.txt', 'new');
        $outside = $this->tmp . '/outside.txt';
        file_put_contents($outside, 'old');
        $dst = $this->tmp . '/destination';
        mkdir($dst, 0700);

        if (!function_exists('link') || !@link($outside, $dst . '/a.txt')) {
            self::markTestSkipped('hard links not supported in this environment');
        }

        self::assertFalse(Filesystem::copyDirectory($src, $dst));
        self::assertSame('old', file_get_contents($outside));
    }

    public function testCopyDirectoryRefusesAJunctionInTheSource(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        $this->junctionOrSkip($src, $src . '/loop');

        self::assertFalse(Filesystem::copyDirectory($src, $this->tmp . '/destination'));
        self::assertFileDoesNotExist($this->tmp . '/destination/loop');
    }

    public function testCopyDirectoryRefusesAJunctionInTheDestination(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src . '/sub', 0700, true);
        file_put_contents($src . '/sub/payload.txt', 'payload');
        $outside = $this->tmp . '/outside';
        mkdir($outside, 0700);
        $dst = $this->tmp . '/destination';
        mkdir($dst, 0700);
        $this->junctionOrSkip($outside, $dst . '/sub');

        self::assertFalse(Filesystem::copyDirectory($src, $dst));
        self::assertFileDoesNotExist($outside . '/payload.txt');
    }

    public function testCopyDirectoryReturnsFalseForAnEmptySource(): void
    {
        // realpath('') is the working directory, which must not be copied.
        self::assertFalse(Filesystem::copyDirectory('', $this->tmp . '/dst'));
        self::assertDirectoryDoesNotExist($this->tmp . '/dst');
    }

    public function testCopyDirectoryKeepsTheModeOfAReplacedFile(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permissions');
        }

        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/secret.txt', 'new');
        chmod($src . '/secret.txt', 0640);
        $dst = $this->tmp . '/destination';
        mkdir($dst, 0700);
        file_put_contents($dst . '/secret.txt', 'old');
        chmod($dst . '/secret.txt', 0600);

        self::assertTrue(Filesystem::copyDirectory($src, $dst));
        clearstatcache();
        self::assertSame('new', file_get_contents($dst . '/secret.txt'));
        self::assertSame(0600, fileperms($dst . '/secret.txt') & 0777);
    }

    public function testCopyDirectoryCopiesAFileNamedNearTheLengthLimit(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('MAX_PATH, not the name limit, binds first on Windows');
        }

        // A temporary name derived from this one would pass the 255-byte name limit.
        $name = str_repeat('n', 250);
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/' . $name, 'x');

        self::assertTrue(Filesystem::copyDirectory($src, $this->tmp . '/destination'));
        self::assertSame('x', file_get_contents($this->tmp . '/destination/' . $name));
    }

    public function testCopyDirectoryReturnsFalseForAnEmptyDestination(): void
    {
        $src = $this->tmp . '/source';
        mkdir($src, 0700);
        file_put_contents($src . '/stray.txt', 'x');

        self::assertFalse(Filesystem::copyDirectory($src, ''));
        self::assertFileDoesNotExist(getcwd() . '/stray.txt');
    }

    public function testCopyDirectoryReturnsFalseOntoAnExistingFile(): void
    {
        mkdir($this->tmp . '/empty', 0700);
        file_put_contents($this->tmp . '/file.txt', 'keep');

        self::assertFalse(Filesystem::copyDirectory($this->tmp . '/empty', $this->tmp . '/file.txt'));
        self::assertSame('keep', file_get_contents($this->tmp . '/file.txt'));
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

    public function testIsWritableRecursiveDoesNotFollowADirectorySymlink(): void
    {
        // A link back to the root would recurse until the path grows too long.
        $dir = $this->tmp . '/looped';
        mkdir($dir);
        $this->symlinkOrSkip($dir, $dir . '/loop');

        self::assertSame(['.', '..', 'loop'], scandir($dir));
        self::assertTrue(Filesystem::isWritableRecursive($dir));
    }

    public function testIsWritableRecursiveChecksNestedEntriesOfARelativePath(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('root can write a read-only file');
        }

        mkdir($this->tmp . '/rel/sub', 0777, true);
        file_put_contents($this->tmp . '/rel/sub/locked.txt', 'x');
        chmod($this->tmp . '/rel/sub/locked.txt', 0444);
        $cwd = getcwd();
        self::assertIsString($cwd);
        chdir($this->tmp);

        try {
            self::assertFalse(Filesystem::isWritableRecursive('rel'));
        } finally {
            chdir($cwd);
            chmod($this->tmp . '/rel/sub/locked.txt', 0644);
        }
    }

    public function testIsWritableRecursiveDoesNotFollowAJunction(): void
    {
        $dir = $this->tmp . '/junctioned';
        mkdir($dir);
        $this->junctionOrSkip($dir, $dir . '/loop');

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

    public function testZipSkipsSymlinkPointingOutsideBaseDirectory(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        $src = $this->tmp . '/to_zip';
        mkdir($src, 0775, true);
        file_put_contents($src . '/inside.txt', 'inside');

        // A file outside the tree, reached only through a symlink placed inside it.
        $outside = $this->tmp . '/outside.txt';
        file_put_contents($outside, 'secret');
        if (!@symlink($outside, $src . '/link.txt')) {
            self::markTestSkipped('symlinks not supported in this environment');
        }

        $zipPath = $this->tmp . '/archive.zip';
        self::assertTrue(Filesystem::zip($src, $zipPath));

        $dst = $this->tmp . '/unzipped';
        self::assertTrue(Filesystem::unzip($zipPath, $dst));

        // The in-tree file is archived; the escaping symlink (and its target) are not.
        self::assertFileExists($dst . '/inside.txt');
        self::assertFileDoesNotExist($dst . '/link.txt');
        self::assertFileDoesNotExist($dst . '/outside.txt');
    }

    public function testZipSkipsSymlinkToSiblingWhoseBackslashNameLooksInside(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('a backslash is a separator on Windows');
        }

        $src = $this->tmp . '/src';
        mkdir($src, 0775, true);
        file_put_contents($src . '/inside.txt', 'inside');

        // "src\secret.txt" sits beside src/, outside it; folding "\" to "/" would
        // make its real path read as src/secret.txt and pass the containment check.
        $outside = $this->tmp . '/src' . chr(92) . 'secret.txt';
        file_put_contents($outside, 'secret');
        if (!@symlink($outside, $src . '/link.txt')) {
            self::markTestSkipped('symlinks not supported in this environment');
        }

        $zipPath = $this->tmp . '/archive.zip';
        self::assertTrue(Filesystem::zip($src, $zipPath));

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        self::assertSame(['inside.txt'], $names);
    }

    public function testZipSkipsInTreeFileWithLiteralBackslashInName(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('a backslash is a separator on Windows');
        }

        $src = $this->tmp . '/src';
        mkdir($src, 0775, true);
        file_put_contents($src . '/inside.txt', 'inside');
        // As a member name this would extract as a path ("a" then "..") on Windows.
        file_put_contents($src . '/a' . chr(92) . '..' . chr(92) . 'x.txt', 'x');

        $zipPath = $this->tmp . '/archive.zip';
        self::assertTrue(Filesystem::zip($src, $zipPath));

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));
        self::assertSame(1, $zip->numFiles);
        self::assertSame('inside.txt', $zip->getNameIndex(0));
        $zip->close();
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

    public function testUnzipRefusesToWriteThroughALinkedDirectoryInTheDestination(): void
    {
        // The member name is clean; the existing destination is not: "sub" leads outside it.
        $outside = $this->tmp . '/outside';
        mkdir($outside, 0700);
        $dst = $this->tmp . '/dest';
        mkdir($dst, 0700);
        $this->symlinkOrSkip($outside, $dst . '/sub');
        $zipPath = $this->buildZip(['first.txt' => 'valid', 'sub/planted.txt' => 'payload']);

        // All or nothing: the valid member before the refused one is not written either.
        self::assertFalse(Filesystem::unzip($zipPath, $dst));
        self::assertFileDoesNotExist($outside . '/planted.txt');
        self::assertFileDoesNotExist($dst . '/first.txt');
    }

    public function testUnzipRefusesToWriteThroughADanglingFileLinkInTheDestination(): void
    {
        // A link to a file that does not exist yet: writing the member would create it.
        $target = $this->tmp . '/created-outside.txt';
        $dst = $this->tmp . '/dest';
        mkdir($dst, 0700);
        $this->symlinkOrSkip($target, $dst . '/file.txt');

        self::assertFalse(Filesystem::unzip($this->buildZip(['file.txt' => 'payload']), $dst));
        self::assertFileDoesNotExist($target);
    }

    public function testUnzipWalksAnInnerDotComponentAsTheSamePath(): void
    {
        // ext/zip writes "a/./file.txt" as "a/file.txt", so the link there is found.
        $target = $this->tmp . '/created-outside.txt';
        $dst = $this->tmp . '/dest';
        mkdir($dst . '/a', 0700, true);
        $this->symlinkOrSkip($target, $dst . '/a/file.txt');

        $zipPath = $this->buildZip(['a/./file.txt' => 'payload']);
        self::assertFalse(Filesystem::unzip($zipPath, $dst));
        self::assertFileDoesNotExist($target);

        // Pin the extraction side too: a plain extract writes exactly the walked path.
        $clean = $this->tmp . '/clean';
        mkdir($clean, 0700);
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));
        self::assertTrue($zip->extractTo($clean));
        $zip->close();
        self::assertFileExists($clean . '/a/file.txt');
    }

    public function testUnzipChecksABackslashNameAsThePlatformWritesIt(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('a backslash is a separator on Windows');
        }

        // On POSIX "a\b.txt" is one filename. Walking it as "a" then "b.txt" would check
        // a path that is never written and miss the link that is.
        $target = $this->tmp . '/created-outside.txt';
        $dst = $this->tmp . '/dest';
        mkdir($dst, 0700);
        $name = 'a' . chr(92) . 'b.txt';
        $this->symlinkOrSkip($target, $dst . '/' . $name);

        self::assertFalse(Filesystem::unzip($this->buildZip([$name => 'payload']), $dst));
        self::assertFileDoesNotExist($target);
    }

    public function testUnzipRefusesADotEndingDirectoryThatExtractionWouldDrop(): void
    {
        // ext/zip drops everything up to a "<name>./" component, so this member is written
        // as "escape/payload.txt" while "missing./..." does not exist to be checked.
        $outside = $this->tmp . '/outside';
        mkdir($outside, 0700);
        $dst = $this->tmp . '/dest';
        mkdir($dst, 0700);
        $this->symlinkOrSkip($outside, $dst . '/escape');

        self::assertFalse(Filesystem::unzip($this->buildZip(['missing./escape/payload.txt' => 'payload']), $dst));
        self::assertFileDoesNotExist($outside . '/payload.txt');

        // The walk stops at the first missing component, so a later "<name>./" must be found too.
        self::assertFalse(Filesystem::unzip($this->buildZip(['absent/missing./escape/payload.txt' => 'payload']), $dst));
        self::assertFileDoesNotExist($outside . '/payload.txt');
    }

    public function testUnzipRefusesAColonInAMemberNameOnWindows(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::markTestSkipped('a colon is an ordinary filename character on POSIX');
        }

        // Extraction drops the "C:" from the basename, so the file written is not the one checked.
        self::assertFalse(Filesystem::unzip($this->buildZip(['sub/C:payload.txt' => 'payload']), $this->tmp . '/dest'));
        self::assertFileDoesNotExist($this->tmp . '/dest/sub/payload.txt');
    }

    public function testUnzipKeepsABackslashEndingDestinationOnPosix(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('a backslash is a separator on Windows');
        }

        // "out\" is its own directory beside "out"; trimming it as a separator would extract into "out".
        $dst = $this->tmp . '/out' . chr(92);
        mkdir($this->tmp . '/out', 0700);
        mkdir($dst, 0700);

        self::assertTrue(Filesystem::unzip($this->buildZip(['file.txt' => 'x']), $dst));
        self::assertFileExists($dst . '/file.txt');
        self::assertFileDoesNotExist($this->tmp . '/out/file.txt');
    }

    public function testUnzipRefusesAnArchiveExpandingPastTheByteBudget(): void
    {
        $zipPath = $this->buildZip(['a.txt' => str_repeat('a', 600), 'b.txt' => str_repeat('b', 600)]);
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));
        self::assertTrue($zip->addEmptyDir('empty'));
        self::assertTrue($zip->close());
        $dst = $this->tmp . '/dest';

        // 1200 bytes in total: each member fits, the sum does not. All or nothing.
        self::assertFalse(Filesystem::unzip($zipPath, $dst, 1000));
        self::assertFileDoesNotExist($dst . '/a.txt');

        self::assertTrue(Filesystem::unzip($zipPath, $dst, 1200));
        self::assertDirectoryExists($dst . '/empty');
        self::assertTrue(Filesystem::unzip($zipPath, $this->tmp . '/unlimited', null));
        self::assertSame(600, filesize($this->tmp . '/unlimited/b.txt'));
    }

    public function testUnzipReturnsFalseWithoutAWarningForACorruptMember(): void
    {
        $zipPath = $this->buildZip(['a.txt' => str_repeat('a', 100000)]);
        $bytes = file_get_contents($zipPath);
        self::assertIsString($bytes);

        // The deflated data starts after the 30-byte local header and the 5-byte name.
        file_put_contents($zipPath, substr_replace($bytes, str_repeat("\xFF", 20), 40, 20));

        // failOnWarning: a warning from the size check would fail this test.
        self::assertFalse(Filesystem::unzip($zipPath, $this->tmp . '/dest'));
        self::assertFileDoesNotExist($this->tmp . '/dest/a.txt');
    }

    public function testUnzipCountsAMemberAcrossSeveralReads(): void
    {
        // Larger than one 64 KiB read, so the count must carry across chunks.
        $zipPath = $this->buildZip(['big.txt' => str_repeat('z', 200000)]);

        self::assertFalse(Filesystem::unzip($zipPath, $this->tmp . '/dest', 199999));
        self::assertFileDoesNotExist($this->tmp . '/dest/big.txt');
        self::assertTrue(Filesystem::unzip($zipPath, $this->tmp . '/dest', 200000));
        self::assertSame(200000, filesize($this->tmp . '/dest/big.txt'));
    }

    public function testUnzipCountsTheBytesAMemberExpandsToNotItsDeclaredSize(): void
    {
        // Patch the uncompressed size in the local header (offset 22) and the central
        // directory entry (offset 24) down to 10 bytes; the member still expands to 5000.
        $zipPath = $this->buildZip(['big.txt' => str_repeat('A', 5000)]);
        $bin = (string) file_get_contents($zipPath);
        $bin = substr_replace($bin, pack('V', 10), (int) strpos($bin, "PK\x03\x04") + 22, 4);
        $bin = substr_replace($bin, pack('V', 10), (int) strpos($bin, "PK\x01\x02") + 24, 4);
        file_put_contents($zipPath, $bin);
        $dst = $this->tmp . '/dest';

        self::assertFalse(Filesystem::unzip($zipPath, $dst, 1000));
        self::assertFileDoesNotExist($dst . '/big.txt');
    }

    public function testUnzipRefusesARepeatedMemberName(): void
    {
        // Renaming "dupB.txt" to "dupA.txt" in the headers gives two members with one name.
        $zipPath = $this->buildZip(['dupA.txt' => str_repeat('a', 600), 'dupB.txt' => '']);
        file_put_contents($zipPath, str_replace('dupB.txt', 'dupA.txt', (string) file_get_contents($zipPath)));
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));
        self::assertSame(2, $zip->numFiles);
        $zip->close();

        self::assertFalse(Filesystem::unzip($zipPath, $this->tmp . '/dest', 1000));
        self::assertFileDoesNotExist($this->tmp . '/dest/dupA.txt');
    }

    public function testUnzipIntoAnExistingDestinationWithRealSubdirectories(): void
    {
        $dst = $this->tmp . '/dest';
        mkdir($dst . '/sub', 0700, true);
        file_put_contents($dst . '/sub/old.txt', 'old');
        $zipPath = $this->buildZip(['sub/old.txt' => 'new', 'sub/deeper/more.txt' => 'more']);

        self::assertTrue(Filesystem::unzip($zipPath, $dst));
        self::assertSame('new', file_get_contents($dst . '/sub/old.txt'));
        self::assertSame('more', file_get_contents($dst . '/sub/deeper/more.txt'));
    }

    /**
     * @param array<string, string> $members member name => contents
     */
    private function buildZip(array $members): string
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        $zipPath = $this->tmp . '/' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath, \ZipArchive::CREATE));
        foreach ($members as $name => $contents) {
            self::assertTrue($zip->addFromString($name, $contents));
        }
        self::assertTrue($zip->close());

        return $zipPath;
    }

    private function junctionOrSkip(string $target, string $junction): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::markTestSkipped('junctions exist only on Windows');
        }

        // Unlike a symlink, a junction needs no privilege.
        $toWindows = static fn (string $p): string => str_replace('/', DIRECTORY_SEPARATOR, $p);
        exec('cmd /c mklink /J ' . escapeshellarg($toWindows($junction)) . ' ' . escapeshellarg($toWindows($target)), $output, $code);
        self::assertSame(0, $code, implode("\n", $output));
        $this->junctions[] = $junction;
    }

    private function bindMountOrSkip(string $target, string $mountPoint): void
    {
        // Needs root with CAP_SYS_ADMIN (a privileged container); skipped everywhere else.
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            self::markTestSkipped('bind mounts need root on Linux');
        }

        exec('mount --bind ' . escapeshellarg($target) . ' ' . escapeshellarg($mountPoint) . ' 2>&1', $output, $code);

        if ($code !== 0) {
            self::markTestSkipped('bind mount not permitted: ' . implode(' ', $output));
        }

        $this->mounts[] = $mountPoint;
    }

    private function symlinkOrSkip(string $target, string $link): void
    {
        // A symlink() listed in disable_functions throws rather than returning false.
        if (!function_exists('symlink') || !@symlink($target, $link)) {
            self::markTestSkipped('symlinks not supported in this environment');
        }
    }

    public function testZipReturnsFalseOnMissingDirectory(): void
    {
        if (!class_exists('ZipArchive')) {
            self::markTestSkipped('ext-zip not available');
        }

        self::assertFalse(Filesystem::zip($this->tmp . '/nosuchdir', $this->tmp . '/out.zip'));
    }

    // ── H2: single-file copy ────────────────────────────────

    public function testCopyCreatesMissingDestDirAndCopiesContents(): void
    {
        $src = $this->tmp . '/src.txt';
        $dst = $this->tmp . '/new/dir/dst.txt';
        file_put_contents($src, 'PAYLOAD');

        $result = Filesystem::copy($src, $dst);

        self::assertTrue($result);
        self::assertFileExists($dst);
        self::assertSame('PAYLOAD', file_get_contents($dst));
        // Source is left intact by a copy
        self::assertFileExists($src);
    }

    public function testCopyReturnsFalseWhenSourceIsNotAFile(): void
    {
        self::assertFalse(Filesystem::copy($this->tmp . '/missing.txt', $this->tmp . '/out.txt'));
    }

    public function testCopyReturnsFalseWhenSourceIsADirectory(): void
    {
        $dir = $this->tmp . '/adir';
        mkdir($dir);

        self::assertFalse(Filesystem::copy($dir, $this->tmp . '/out.txt'));
    }

    // ── H2: single-file delete ──────────────────────────────

    public function testDeleteRemovesFile(): void
    {
        $path = $this->tmp . '/gone.txt';
        file_put_contents($path, 'x');

        $result = Filesystem::delete($path);

        self::assertTrue($result);
        self::assertFileDoesNotExist($path);
    }

    public function testDeleteReturnsTrueWhenAlreadyAbsent(): void
    {
        self::assertTrue(Filesystem::delete($this->tmp . '/never_existed.txt'));
    }

    public function testDeleteReturnsFalseForRealDirectory(): void
    {
        $dir = $this->tmp . '/realdir';
        mkdir($dir);

        self::assertFalse(Filesystem::delete($dir));
        self::assertDirectoryExists($dir);
    }

    // ── H2: single-file move ────────────────────────────────

    public function testMoveRelocatesFileAndCreatesDestDir(): void
    {
        $src = $this->tmp . '/from.txt';
        $dst = $this->tmp . '/moved/to.txt';
        file_put_contents($src, 'DATA');

        $result = Filesystem::move($src, $dst);

        self::assertTrue($result);
        self::assertFileExists($dst);
        self::assertSame('DATA', file_get_contents($dst));
        self::assertFileDoesNotExist($src);
    }

    public function testMoveReturnsFalseWhenSourceIsNotAFile(): void
    {
        self::assertFalse(Filesystem::move($this->tmp . '/missing.txt', $this->tmp . '/out.txt'));
    }

    // ── H2: rename is an alias of move ──────────────────────

    public function testRenameBehavesAsMove(): void
    {
        $src = $this->tmp . '/orig.txt';
        $dst = $this->tmp . '/renamed/new.txt';
        file_put_contents($src, 'CONTENT');

        $result = Filesystem::rename($src, $dst);

        self::assertTrue($result);
        self::assertFileExists($dst);
        self::assertSame('CONTENT', file_get_contents($dst));
        self::assertFileDoesNotExist($src);
    }

    public function testRenameReturnsFalseWhenSourceMissing(): void
    {
        self::assertFalse(Filesystem::rename($this->tmp . '/nope.txt', $this->tmp . '/out.txt'));
    }

    // ── H7: secureDir ───────────────────────────────────────

    public function testSecureDirCreatesDirectoryAndIndexGuard(): void
    {
        $dir = $this->tmp . '/secured/nested';

        $result = Filesystem::secureDir($dir);

        self::assertTrue($result);
        self::assertDirectoryExists($dir);

        $guard = $dir . '/index.html';
        self::assertFileExists($guard);
        self::assertStringContainsString('history.go(-1)', file_get_contents($guard));
    }

    public function testSecureDirReturnsTrueWhenDirectoryAlreadyExists(): void
    {
        $dir = $this->tmp . '/already';
        mkdir($dir);

        $result = Filesystem::secureDir($dir);

        self::assertTrue($result);
        self::assertFileExists($dir . '/index.html');
    }

    public function testSecureDirPreservesExistingGuardFile(): void
    {
        $dir = $this->tmp . '/guarded';
        mkdir($dir);
        $guard = $dir . '/index.html';
        file_put_contents($guard, 'custom guard');

        $result = Filesystem::secureDir($dir);

        self::assertTrue($result);
        self::assertSame('custom guard', file_get_contents($guard));
    }
}
