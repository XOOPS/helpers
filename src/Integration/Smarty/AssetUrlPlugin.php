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

namespace Xoops\Helpers\Integration\Smarty;

use Xoops\Helpers\Service\Url;

/**
 * Smarty plugin for generating asset URLs.
 *
 * Template usage (XOOPS delimiters):
 *   <{asset_url path="js/main.js"}>
 *   <{asset_url path="css/style.css" secure=true}>
 *
 * Register in module bootstrap:
 *   AssetUrlPlugin::register($smarty);
 */
final class AssetUrlPlugin
{
    /**
     * Register this plugin with a Smarty instance.
     */
    public static function register(object $smarty): void
    {
        if (method_exists($smarty, 'registerPlugin')) {
            $smarty->registerPlugin('function', 'asset_url', [self::class, 'render']);
        }
    }

    /**
     * Smarty function callback.
     *
     * @param array<string, mixed> $params  Template parameters
     * @param object               $smarty  Smarty instance
     */
    public static function render(array $params, object $smarty): string
    {
        $path = (string) ($params['path'] ?? '');
        $secureParam = $params['secure'] ?? false;
        $secure = is_bool($secureParam)
            ? $secureParam
            : (filter_var($secureParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);

        return Url::asset($path, $secure);
    }
}
