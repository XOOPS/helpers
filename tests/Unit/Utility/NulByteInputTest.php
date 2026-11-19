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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xoops\Helpers\Service\Config;
use Xoops\Helpers\Service\Path;
use Xoops\Helpers\Utility\Data;
use Xoops\Helpers\Utility\Environment;
use Xoops\Helpers\Utility\Filesystem;
use ZipArchive;

/**
 * A NUL byte in a path, a query string or a variable name is reported as a failure.
 *
 * PHP 8.6 throws a ValueError from every function taking a path, from parse_str() and
 * from getenv() when the argument holds a NUL byte; fopen(), mkdir(), realpath() and
 * ZipArchive::open() did so before.
 */
final class NulByteInputTest extends TestCase
{
    private const NUL = "a\0b";

    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = XOOPS_ROOT_PATH . '/nul_test_' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->tmp, 0700));
        self::assertTrue(mkdir($this->tmp . '/dir'));
        self::assertNotFalse(file_put_contents($this->tmp . '/dir/file.txt', 'content'));
    }

    protected function tearDown(): void
    {
        Config::reset();
        Filesystem::deleteDirectory($this->tmp);
    }

    /**
     * @return iterable<string, array{0: \Closure(string): mixed, 1: mixed}>
     */
    public static function filesystemCalls(): iterable
    {
        $n = self::NUL;
        $file = '/dir/file.txt';

        yield 'readChunked' => [static fn(string $t) => Filesystem::readChunked("$t/$n", 10, static fn() => true), false];
        yield 'readJson' => [static fn(string $t) => Filesystem::readJson("$t/$n"), null];
        yield 'putJson' => [static fn(string $t) => Filesystem::putJson("$t/$n", []), false];
        yield 'mimeType' => [static fn(string $t) => Filesystem::mimeType("$t/$n"), null];
        yield 'isImage' => [static fn(string $t) => Filesystem::isImage("$t/$n"), false];
        // The extension alone would pass: no file can have this name, so it is no image.
        yield 'isImage with an image extension' => [static fn(string $t) => Filesystem::isImage("$t/$n.png"), false];
        yield 'extension' => [static fn(string $t) => Filesystem::extension("$t/$n.png"), ''];
        yield 'size' => [static fn(string $t) => Filesystem::size("$t/$n"), false];
        yield 'isWritableRecursive' => [static fn(string $t) => Filesystem::isWritableRecursive("$t/$n"), false];
        yield 'mkdir' => [static fn(string $t) => Filesystem::mkdir("$t/$n"), false];
        yield 'secureDir' => [static fn(string $t) => Filesystem::secureDir("$t/$n"), false];
        yield 'copy from' => [static fn(string $t) => Filesystem::copy("$t/$n", "$t/copy.txt"), false];
        yield 'copy to' => [static fn(string $t) => Filesystem::copy($t . $file, "$t/$n/copy.txt"), false];
        yield 'move from' => [static fn(string $t) => Filesystem::move("$t/$n", "$t/moved.txt"), false];
        yield 'move to' => [static fn(string $t) => Filesystem::move($t . $file, "$t/$n/moved.txt"), false];
        yield 'rename to' => [static fn(string $t) => Filesystem::rename($t . $file, "$t/$n"), false];
        // Nothing can exist under such a name, and delete() reports an absent path as done.
        yield 'delete' => [static fn(string $t) => Filesystem::delete("$t/$n"), true];
        yield 'deleteDirectory' => [static fn(string $t) => Filesystem::deleteDirectory("$t/$n"), false];
        yield 'copyDirectory from' => [static fn(string $t) => Filesystem::copyDirectory("$t/$n", "$t/out"), false];
        yield 'copyDirectory to' => [static fn(string $t) => Filesystem::copyDirectory("$t/dir", "$t/$n"), false];
        yield 'moveDirectory from' => [static fn(string $t) => Filesystem::moveDirectory("$t/$n", "$t/out"), false];
        yield 'moveDirectory to' => [static fn(string $t) => Filesystem::moveDirectory("$t/dir", "$t/$n"), false];
        yield 'zip from' => [static fn(string $t) => Filesystem::zip("$t/$n", "$t/out.zip"), false];
        yield 'zip to' => [static fn(string $t) => Filesystem::zip("$t/dir", "$t/$n.zip"), false];
        yield 'unzip from' => [static fn(string $t) => Filesystem::unzip("$t/$n.zip", "$t/out"), false];
    }

    /**
     * @param \Closure(string): mixed $call
     */
    #[DataProvider('filesystemCalls')]
    public function testFilesystemReportsANulBytePathAsAFailure(\Closure $call, mixed $expected): void
    {
        self::assertSame($expected, $call($this->tmp));
        // The source of a refused copy or move is left in place.
        self::assertFileExists($this->tmp . '/dir/file.txt');
    }

    public function testUnzipRefusesANulByteDestination(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('ext-zip is not loaded');
        }
        $zipPath = $this->tmp . '/in.zip';
        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath, ZipArchive::CREATE));
        $zip->addFromString('file.txt', 'content');
        $zip->close();

        self::assertFalse(Filesystem::unzip($zipPath, $this->tmp . '/' . self::NUL));
    }

    public function testFromQueryStringReturnsNothingForARawNulByte(): void
    {
        self::assertSame([], Data::fromQueryString("a=1&b=x\0y"));
    }

    public function testFromQueryStringStillDecodesAnEncodedNulByte(): void
    {
        self::assertSame(['a' => "x\0y"], Data::fromQueryString('a=x%00y'));
    }

    public function testEnvironmentTreatsANulByteNameAsUnset(): void
    {
        self::assertSame('fallback', Environment::get(self::NUL, 'fallback'));
        self::assertFalse(Environment::has(self::NUL));

        $this->expectException(RuntimeException::class);
        Environment::require(self::NUL);
    }

    public function testLanguageFileReturnsThePrimaryPathForANulByteLanguage(): void
    {
        self::assertSame(
            Path::module('nulmod', 'language/' . self::NUL . '/main.php'),
            Path::languageFile('nulmod', self::NUL, 'main.php'),
        );
    }

    public function testConfigFileFallbackIgnoresANulByteModuleName(): void
    {
        self::assertSame([], Config::all(self::NUL));
    }
}
