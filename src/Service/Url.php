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

namespace Xoops\Helpers\Service;

use Xoops\Helpers\Contracts\UrlGeneratorInterface;
use Xoops\Helpers\Provider\DefaultUrlGenerator;

/**
 * Static facade for XOOPS URL generation.
 *
 * Zero-config: works out of the box using XOOPS_URL constant.
 * Override via Url::use() for testing or reverse proxy setups.
 *
 * Usage:
 *   Url::to('modules/news/index.php');
 *   Url::asset('themes/starter/css/style.css');
 *   Url::module('news', 'article.php', ['id' => 42]);
 *   Url::theme('starter', 'css/style.css');
 */
final class Url
{
    private static ?UrlGeneratorInterface $generator = null;

    /**
     * Inject a custom URL generator (useful for testing).
     */
    public static function use(UrlGeneratorInterface $generator): void
    {
        self::$generator = $generator;
    }

    /**
     * Reset to the default generator.
     */
    public static function reset(): void
    {
        self::$generator = null;
    }

    /**
     * Generate a URL to a path.
     *
     * @param string              $path   Relative path
     * @param array<string,mixed> $query  Query parameters
     * @param bool                $secure Force HTTPS
     */
    public static function to(string $path = '', array $query = [], bool $secure = false): string
    {
        return self::generator()->generate($path, $query, $secure);
    }

    /**
     * Generate a URL to a static asset.
     */
    public static function asset(string $path, bool $secure = false): string
    {
        return self::generator()->asset($path, $secure);
    }

    /**
     * Generate a URL to a module path.
     *
     * @param string              $dirname Module directory name
     * @param string              $path    Relative path within the module
     * @param array<string,mixed> $query   Query parameters
     */
    public static function module(string $dirname, string $path = '', array $query = []): string
    {
        return self::generator()->module($dirname, $path, $query);
    }

    /**
     * Generate a URL to a theme asset.
     */
    public static function theme(string $name, string $path = ''): string
    {
        return self::generator()->theme($name, $path);
    }

    private static function generator(): UrlGeneratorInterface
    {
        return self::$generator ??= new DefaultUrlGenerator();
    }
}
