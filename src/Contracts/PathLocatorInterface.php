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
 * Contract for resolving XOOPS filesystem paths.
 *
 * Implementations map logical path names to absolute filesystem paths.
 * The default implementation uses XOOPS constants; custom implementations
 * can be injected for testing or non-standard installations.
 */
interface PathLocatorInterface
{
    /**
     * Get the base installation path (XOOPS_ROOT_PATH).
     */
    public function basePath(string $path = ''): string;

    /**
     * Get the public web-accessible path.
     */
    public function publicPath(string $path = ''): string;

    /**
     * Get the private storage/data path (XOOPS_VAR_PATH).
     */
    public function storagePath(string $path = ''): string;

    /**
     * Get the uploads directory path (XOOPS_UPLOAD_PATH).
     */
    public function uploadsPath(string $path = ''): string;

    /**
     * Get the modules directory path.
     */
    public function modulesPath(string $path = ''): string;

    /**
     * Get the themes directory path.
     */
    public function themesPath(string $path = ''): string;

    /**
     * Get a module-specific path.
     *
     * @param string $dirname Module directory name
     * @param string $path    Relative path within the module
     */
    public function modulePath(string $dirname, string $path = ''): string;

    /**
     * Get a theme-specific path.
     *
     * @param string $name Theme name
     * @param string $path Relative path within the theme
     */
    public function themePath(string $name, string $path = ''): string;
}
