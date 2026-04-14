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

use Xoops\Helpers\Utility\Number;

/**
 * Smarty plugin for number formatting.
 *
 * Template usage (XOOPS delimiters):
 *   <{format_number value=$price decimals=2}>
 *   <{format_number value=$filesize type="filesize"}>
 *   <{format_number value=$count type="human"}>
 *   <{format_number value=$pct type="percentage"}>
 *
 * Register:
 *   FormatNumberPlugin::register($smarty);
 */
final class FormatNumberPlugin
{
    /**
     * Register this plugin with a Smarty instance.
     */
    public static function register(object $smarty): void
    {
        if (method_exists($smarty, 'registerPlugin')) {
            $smarty->registerPlugin('function', 'format_number', [self::class, 'render']);
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
        $value = $params['value'] ?? 0;
        $type = (string) ($params['type'] ?? 'decimal');
        $hasDecimals = array_key_exists('decimals', $params);
        $decimals = (int) ($params['decimals'] ?? 0);
        $locale = isset($params['locale']) ? (string) $params['locale'] : null;

        return match ($type) {
            'filesize' => Number::fileSize((int) $value, $hasDecimals ? $decimals : 2),
            'human' => Number::forHumans((float) $value, $hasDecimals ? $decimals : 1),
            'percentage' => Number::percentage((float) $value, $decimals, $locale),
            'ordinal' => Number::ordinal((int) $value, $locale),
            'currency' => Number::currency(
                (float) $value,
                (string) ($params['currency'] ?? 'USD'),
                $locale ?? 'en_US',
            ),
            default => Number::format((float) $value, $decimals, $locale),
        };
    }
}
