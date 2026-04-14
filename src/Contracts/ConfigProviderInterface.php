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
 * Contract for configuration data providers.
 *
 * Implementations load configuration data from a source
 * (database, file, array) for a given module.
 */
interface ConfigProviderInterface
{
    /**
     * Load all configuration values for a module.
     *
     * @param string $module Module directory name (e.g. "system", "news")
     * @return array<string, mixed> Configuration key-value pairs
     */
    public function load(string $module): array;

    /**
     * Save configuration values for a module.
     *
     * @param string              $module Module directory name
     * @param array<string,mixed> $config Configuration to save
     */
    public function save(string $module, array $config): bool;

    /**
     * Check if this provider supports loading config for the given module.
     */
    public function supports(string $module): bool;
}
