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

namespace Xoops\Helpers\Provider;

use Xoops\Helpers\Contracts\PathLocatorInterface;

/**
 * Default path locator using XOOPS constants.
 *
 * Maps logical paths to XOOPS filesystem locations:
 * - base    -> XOOPS_ROOT_PATH
 * - public  -> XOOPS_ROOT_PATH (same as base in standard XOOPS)
 * - storage -> XOOPS_VAR_PATH (or fallback to XOOPS_ROOT_PATH/xoops_data)
 * - uploads -> XOOPS_UPLOAD_PATH (or fallback to XOOPS_ROOT_PATH/uploads)
 * - modules -> XOOPS_ROOT_PATH/modules
 * - themes  -> XOOPS_ROOT_PATH/themes
 */
class DefaultPathLocator implements PathLocatorInterface
{
    public function basePath(string $path = ''): string
    {
        return self::join(self::rootPath(), $path);
    }

    public function publicPath(string $path = ''): string
    {
        return self::join(self::rootPath(), $path);
    }

    public function storagePath(string $path = ''): string
    {
        $base = defined('XOOPS_VAR_PATH')
            ? XOOPS_VAR_PATH
            : self::rootPath() . DIRECTORY_SEPARATOR . 'xoops_data';

        return self::join($base, $path);
    }

    public function uploadsPath(string $path = ''): string
    {
        $base = defined('XOOPS_UPLOAD_PATH')
            ? XOOPS_UPLOAD_PATH
            : self::rootPath() . DIRECTORY_SEPARATOR . 'uploads';

        return self::join($base, $path);
    }

    public function modulesPath(string $path = ''): string
    {
        return self::join(self::rootPath() . DIRECTORY_SEPARATOR . 'modules', $path);
    }

    public function themesPath(string $path = ''): string
    {
        return self::join(self::rootPath() . DIRECTORY_SEPARATOR . 'themes', $path);
    }

    public function modulePath(string $dirname, string $path = ''): string
    {
        return self::join($this->modulesPath($dirname), $path);
    }

    public function themePath(string $name, string $path = ''): string
    {
        return self::join($this->themesPath($name), $path);
    }

    private static function rootPath(): string
    {
        return defined('XOOPS_ROOT_PATH') ? XOOPS_ROOT_PATH : '';
    }

    /**
     * Join a base path with a relative path, handling separators.
     */
    private static function join(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}
