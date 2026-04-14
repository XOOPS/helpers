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

use Xoops\Helpers\Utility\HtmlBuilder;

/**
 * Smarty plugin for conditional CSS class rendering.
 *
 * Template usage (XOOPS delimiters):
 *   <{css_classes classes=$classArray}>
 *
 * Where $classArray is assigned in the controller as:
 *   $tpl->assign('classArray', [
 *       'btn',
 *       'btn-primary' => $isPrimary,
 *       'disabled' => $isDisabled,
 *   ]);
 *
 * Register:
 *   CssClassesPlugin::register($smarty);
 */
final class CssClassesPlugin
{
    /**
     * Register this plugin with a Smarty instance.
     */
    public static function register(object $smarty): void
    {
        if (method_exists($smarty, 'registerPlugin')) {
            $smarty->registerPlugin('function', 'css_classes', [self::class, 'render']);
        }
    }

    /**
     * Smarty function callback.
     *
     * @param array<string, mixed> $params Template parameters
     * @param object               $smarty Smarty instance
     */
    public static function render(array $params, object $smarty): string
    {
        $classes = $params['classes'] ?? [];

        if (!is_array($classes)) {
            return HtmlBuilder::escape((string) $classes);
        }

        return HtmlBuilder::classes($classes);
    }
}
