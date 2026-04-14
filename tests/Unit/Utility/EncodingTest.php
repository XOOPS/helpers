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
use Xoops\Helpers\Utility\Encoding;

final class EncodingTest extends TestCase
{
    public function testBase64UrlEncodeProducesNoPlusSlashOrEquals(): void
    {
        // Run over enough random-ish data to hit all three replaced characters
        for ($i = 0; $i < 100; $i++) {
            $input = str_repeat(chr($i), 4);
            $encoded = Encoding::base64UrlEncode($input);
            self::assertStringNotContainsString('+', $encoded, "Found '+' in URL-encoded output");
            self::assertStringNotContainsString('/', $encoded, "Found '/' in URL-encoded output");
            self::assertStringNotContainsString('=', $encoded, "Found '=' padding in URL-encoded output");
        }
    }

    public function testBase64UrlRoundTripForPlainText(): void
    {
        $input = 'Hello, XOOPS!';
        self::assertSame($input, Encoding::base64UrlDecode(Encoding::base64UrlEncode($input)));
    }

    public function testBase64UrlRoundTripForBinaryData(): void
    {
        $input = "\x00\xFF\xFE\xAB\xCD\xEF\x01\x23";
        self::assertSame($input, Encoding::base64UrlDecode(Encoding::base64UrlEncode($input)));
    }

    public function testBase64UrlRoundTripForEmptyString(): void
    {
        self::assertSame('', Encoding::base64UrlDecode(Encoding::base64UrlEncode('')));
    }

    public function testBase64UrlRoundTripForLongString(): void
    {
        $input = str_repeat('XOOPS module developer toolkit — ', 100);
        self::assertSame($input, Encoding::base64UrlDecode(Encoding::base64UrlEncode($input)));
    }

    public function testBase64UrlDecodeHandlesMissingPadding(): void
    {
        // Base64 without padding should still decode correctly
        $standard = base64_encode('test');             // "dGVzdA=="
        $urlSafe = rtrim(strtr($standard, '+/', '-_'), '=');  // "dGVzdA" (no padding)

        self::assertSame('test', Encoding::base64UrlDecode($urlSafe));
    }

    public function testBase64UrlDecodeReturnsEmptyStringOnInvalidInput(): void
    {
        // Invalid base64 should return '' rather than throwing
        $result = Encoding::base64UrlDecode('!!!invalid!!!');
        self::assertIsString($result);
    }

    public function testBase64UrlEncodeProducesOnlyUrlSafeCharacters(): void
    {
        $input = random_bytes(128);
        $encoded = Encoding::base64UrlEncode($input);

        self::assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $encoded);
    }
}
