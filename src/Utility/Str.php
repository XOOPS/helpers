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
 * String utility helpers.
 *
 * Provides case conversion, slug generation, validation,
 * and manipulation methods. Most operations use mbstring
 * for UTF-8 safety where applicable.
 */
final class Str
{
    /**
     * Convert a string to camelCase.
     */
    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    /**
     * Convert a string to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (!ctype_lower($value)) {
            $value = (string) preg_replace('/\s+/u', '', ucwords($value));
            $value = mb_strtolower((string) preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return $value;
    }

    /**
     * Convert a string to StudlyCase (PascalCase).
     */
    public static function studly(string $value): string
    {
        $words = explode(' ', str_replace(['-', '_'], ' ', $value));

        return implode('', array_map('ucfirst', $words));
    }

    /**
     * Convert a string to kebab-case.
     */
    public static function kebab(string $value): string
    {
        return self::snake($value, '-');
    }

    /**
     * Generate a URL-friendly slug.
     *
     * Uses intl transliterator when available for proper
     * Unicode-to-ASCII conversion.
     */
    public static function slug(string $title, string $separator = '-'): string
    {
        if (extension_loaded('intl')) {
            $title = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $title);
        } elseif (function_exists('iconv')) {
            $title = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
        }

        $quotedSep = preg_quote($separator, '/');
        $title = (string) preg_replace('/[^\x20-\x7E]/u', '', $title);
        $title = (string) preg_replace('/[^a-zA-Z0-9\s' . $quotedSep . '-]/u', '', $title);
        $title = (string) preg_replace('/[\s' . $quotedSep . '-]+/', $separator, $title);

        return mb_strtolower(trim($title, $separator));
    }

    /**
     * Truncate a string to a given length with a suffix.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')) . $end;
    }

    /**
     * Generate a cryptographically secure random string.
     */
    public static function random(int $length = 16): string
    {
        return substr(
            strtr(base64_encode(random_bytes(max(1, (int) ceil($length * 0.75)))), '+/', 'Az'),
            0,
            $length,
        );
    }

    /**
     * Determine if a string contains one or more substrings.
     *
     * @param string                   $haystack   String to search in
     * @param string|array<int, string> $needles    Substring(s) to find
     * @param bool                     $ignoreCase Case-insensitive comparison
     */
    public static function contains(string $haystack, string|array $needles, bool $ignoreCase = false): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle === '') {
                continue;
            }

            $found = $ignoreCase
                ? mb_stripos($haystack, $needle, 0, 'UTF-8')
                : mb_strpos($haystack, $needle, 0, 'UTF-8');

            if ($found !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a string starts with one of the given substrings.
     *
     * @param string                   $haystack String to check
     * @param string|array<int, string> $needles  Prefix(es) to match
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a string ends with one of the given substrings.
     *
     * @param string                   $haystack String to check
     * @param string|array<int, string> $needles  Suffix(es) to match
     */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract text between two delimiters.
     */
    public static function between(string $subject, string $from, string $to): string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        $startPos = mb_strpos($subject, $from, 0, 'UTF-8');
        if ($startPos === false) {
            return $subject;
        }

        $startPos += mb_strlen($from, 'UTF-8');
        $endPos = mb_strpos($subject, $to, $startPos, 'UTF-8');
        if ($endPos === false) {
            return $subject;
        }

        return mb_substr($subject, $startPos, $endPos - $startPos, 'UTF-8');
    }

    /**
     * Replace the first occurrence of a substring.
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        $pos = strpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    /**
     * Replace the last occurrence of a substring.
     */
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        $pos = strrpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    /**
     * Count the number of words in a string.
     */
    public static function wordCount(string $string): int
    {
        // Unicode-safe: matches word characters including non-Latin scripts
        preg_match_all('/[\p{L}\p{N}]+/u', $string, $matches);

        return count($matches[0]);
    }

    /**
     * Get the string length in characters (UTF-8).
     */
    public static function length(string $value): int
    {
        return mb_strlen($value, 'UTF-8');
    }

    /**
     * Check if a string is a valid email address.
     */
    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if a string is a valid URL.
     */
    public static function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a string is a valid IP address.
     */
    public static function isIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if a string is valid JSON.
     */
    public static function isJson(string $value): bool
    {
        if (function_exists('json_validate')) {
            return json_validate($value);
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if a string is a valid hex color code.
     */
    public static function isHexColor(string $value): bool
    {
        return (bool) preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value);
    }

    /**
     * Convert the string to ASCII, removing non-ASCII characters.
     */
    public static function ascii(string $value): string
    {
        if (extension_loaded('intl')) {
            return (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
        }

        return (string) preg_replace('/[^\x20-\x7E]/', '', $value);
    }

    /**
     * Mask a portion of a string with a repeated character.
     */
    public static function mask(string $string, string $character, int $index, ?int $length = null): string
    {
        $strLen = mb_strlen($string, 'UTF-8');

        if ($index < 0) {
            $index = max(0, $strLen + $index);
        }

        $length ??= $strLen - $index;
        $segment = mb_substr($string, $index, $length, 'UTF-8');

        return mb_substr($string, 0, $index, 'UTF-8')
            . str_repeat($character, mb_strlen($segment, 'UTF-8'))
            . mb_substr($string, $index + $length, null, 'UTF-8');
    }
}
