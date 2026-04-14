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

namespace Xoops\Helpers\Utility;

/**
 * Encoding utility helpers.
 *
 * Provides URL-safe base64 encoding/decoding for use in
 * JWT tokens, CSRF tokens, and other URL-embedded data.
 */
final class Encoding
{
    /**
     * Encode data to URL-safe base64.
     *
     * Replaces + with -, / with _, and strips trailing = padding.
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode a URL-safe base64 string.
     *
     * Restores standard base64 characters and padding before decoding.
     */
    public static function base64UrlDecode(string $data): string
    {
        $standard = strtr($data, '-_', '+/');
        $remainder = strlen($standard) % 4;

        if ($remainder > 0) {
            $standard .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($standard, true);

        return $decoded !== false ? $decoded : '';
    }
}
