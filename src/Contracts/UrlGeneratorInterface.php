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

namespace Xoops\Helpers\Contracts;

/**
 * Contract for generating XOOPS URLs.
 *
 * Implementations produce absolute URLs based on the XOOPS
 * installation. The default uses XOOPS_URL; custom implementations
 * can be injected for testing or reverse proxies.
 */
interface UrlGeneratorInterface
{
    /**
     * Generate a URL to a path with optional query parameters.
     *
     * @param string              $path   Relative path
     * @param array<string,mixed> $query  Query parameters
     * @param bool                $secure Force HTTPS
     */
    public function generate(string $path = '', array $query = [], bool $secure = false): string;

    /**
     * Generate a URL to a static asset.
     *
     * @param string $path   Asset path relative to site root
     * @param bool   $secure Force HTTPS
     */
    public function asset(string $path, bool $secure = false): string;

    /**
     * Generate a URL to a module path.
     *
     * @param string              $dirname Module directory name
     * @param string              $path    Relative path within the module
     * @param array<string,mixed> $query   Query parameters
     */
    public function module(string $dirname, string $path = '', array $query = []): string;

    /**
     * Generate a URL to a theme asset.
     *
     * @param string $name Theme name
     * @param string $path Relative path within the theme
     */
    public function theme(string $name, string $path = ''): string;
}
