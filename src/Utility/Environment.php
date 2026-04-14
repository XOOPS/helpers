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
 * Runtime environment detection helpers.
 *
 * Detects the current execution environment (production, development,
 * testing) by checking the XOOPS_ENVIRONMENT constant, APP_ENV
 * environment variable, or a configured default.
 *
 * Usage:
 *   if (Environment::isDevelopment()) {
 *       // show debug toolbar
 *   }
 *
 *   $dbHost = Environment::get('XOOPS_DB_HOST', 'localhost');
 */
final class Environment
{
    private static ?string $override = null;

    /**
     * Override the detected environment (useful for testing).
     */
    public static function set(string $environment): void
    {
        self::$override = $environment;
    }

    /**
     * Reset the environment override.
     */
    public static function reset(): void
    {
        self::$override = null;
    }

    /**
     * Get the current environment name.
     *
     * Detection order:
     * 1. Explicit override via set()
     * 2. XOOPS_ENVIRONMENT constant
     * 3. APP_ENV environment variable
     * 4. Falls back to "production"
     */
    public static function current(): string
    {
        if (self::$override !== null) {
            return self::$override;
        }

        if (defined('XOOPS_ENVIRONMENT')) {
            return (string) constant('XOOPS_ENVIRONMENT');
        }

        return self::get('APP_ENV', 'production');
    }

    /**
     * Check if the current environment matches a name.
     */
    public static function is(string $environment): bool
    {
        return strtolower(self::current()) === strtolower($environment);
    }

    public static function isProduction(): bool
    {
        return self::is('production');
    }

    public static function isDevelopment(): bool
    {
        return in_array(strtolower(self::current()), ['development', 'dev', 'local'], true);
    }

    public static function isTesting(): bool
    {
        return in_array(strtolower(self::current()), ['testing', 'test'], true);
    }

    public static function isStaging(): bool
    {
        return in_array(strtolower(self::current()), ['staging', 'stage'], true);
    }

    /**
     * Get an environment variable with a default fallback.
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        return $value !== null && $value !== false ? (string) $value : $default;
    }

    /**
     * Get a required environment variable, throwing on absence.
     *
     * @throws \RuntimeException If the variable is not set
     */
    public static function require(string $key): string
    {
        $value = self::get($key, '');

        if ($value === '') {
            throw new \RuntimeException("Required environment variable '{$key}' is not set.");
        }

        return $value;
    }

    /**
     * Check if an environment variable is set and non-empty.
     */
    public static function has(string $key): bool
    {
        return self::get($key, '') !== '';
    }
}
