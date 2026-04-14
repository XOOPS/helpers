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

use Xoops\Helpers\Contracts\CacheInterface;
use Xoops\Helpers\Contracts\ConfigProviderInterface;
use Xoops\Helpers\Utility\Arr;

/**
 * Configuration service with dot-notation access and caching.
 *
 * Zero-config: loads configuration from XOOPS database or files automatically.
 * Supports per-module lazy loading, caching, and custom loaders.
 *
 * Usage:
 *   Config::get('system.sitename');               // dot-notation access
 *   Config::get('news.items_per_page', 10);       // with default
 *   Config::set('system.sitename', 'My XOOPS');   // in-memory override
 *   Config::all('system');                         // all system configs
 *
 * Custom loaders:
 *   Config::registerLoader('mymod', fn($module) => ['key' => 'value']);
 */
final class Config
{
    /** @var array<string, array<string, mixed>> Loaded configuration per module */
    private static array $loaded = [];

    /** @var array<string, callable> Custom loaders per module */
    private static array $loaders = [];

    /** @var ConfigProviderInterface|null Primary configuration provider */
    private static ?ConfigProviderInterface $provider = null;

    /** @var CacheInterface|null Optional cache adapter */
    private static ?CacheInterface $cache = null;

    /**
     * Get a configuration value using dot notation.
     *
     * The first segment of the key is the module name.
     * Example: 'system.sitename' loads the 'system' module config, then gets 'sitename'.
     *
     * @param string $key     Dot-notated config key (e.g. "module.setting")
     * @param mixed  $default Default value if not found
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $module = self::extractModule($key);
        self::ensureLoaded($module);

        return Arr::get(self::$loaded, $key, $default);
    }

    /**
     * Set a configuration value in memory (does not persist).
     */
    public static function set(string $key, mixed $value): void
    {
        $module = self::extractModule($key);
        self::ensureLoaded($module);

        Arr::set(self::$loaded, $key, $value);

        self::invalidateCache($module);
    }

    /**
     * Check if a configuration key exists.
     */
    public static function has(string $key): bool
    {
        $module = self::extractModule($key);
        self::ensureLoaded($module);

        return Arr::has(self::$loaded, $key);
    }

    /**
     * Remove a configuration key from memory.
     */
    public static function forget(string $key): void
    {
        Arr::forget(self::$loaded, $key);
        self::invalidateCache(self::extractModule($key));
    }

    /**
     * Get all configuration for a module.
     *
     * @return array<string, mixed>
     */
    public static function all(string $module = 'system'): array
    {
        self::ensureLoaded($module);

        return self::$loaded[$module] ?? [];
    }

    /**
     * Register a custom configuration loader for a module.
     *
     * @param string   $module Module directory name
     * @param callable $loader Callback receiving module name, returning array
     */
    public static function registerLoader(string $module, callable $loader): void
    {
        self::$loaders[$module] = $loader;
    }

    /**
     * Set the primary configuration provider.
     */
    public static function setProvider(ConfigProviderInterface $provider): void
    {
        self::$provider = $provider;
    }

    /**
     * Set the cache adapter for configuration data.
     */
    public static function setCache(?CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * Reload configuration for a module (clears cache).
     */
    public static function reload(string $module): void
    {
        unset(self::$loaded[$module]);
        self::invalidateCache($module);
        self::ensureLoaded($module);
    }

    /**
     * Clear all loaded configuration from memory.
     */
    public static function clear(): void
    {
        self::$loaded = [];
    }

    /**
     * Save configuration for a module (delegates to provider).
     *
     * @param string              $module Module directory name
     * @param array<string,mixed> $config Configuration to save
     */
    public static function save(string $module, array $config): bool
    {
        if (self::$provider === null) {
            return false;
        }

        if (!self::$provider->save($module, $config)) {
            return false;
        }

        self::$loaded[$module] = $config;
        self::invalidateCache($module);

        return true;
    }

    /**
     * Reset all state (for testing).
     */
    public static function reset(): void
    {
        self::$loaded = [];
        self::$loaders = [];
        self::$provider = null;
        self::$cache = null;
    }

    private static function ensureLoaded(string $module): void
    {
        if (isset(self::$loaded[$module])) {
            return;
        }

        $cacheKey = "config.{$module}";

        if (self::$cache !== null) {
            $cached = self::$cache->get($cacheKey);

            if (is_array($cached)) {
                self::$loaded[$module] = $cached;

                return;
            }
        }

        $config = self::loadFromSource($module);
        self::$loaded[$module] = $config;

        if (self::$cache !== null && $config !== []) {
            self::$cache->set($cacheKey, $config, 3600);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFromSource(string $module): array
    {
        // 1. Custom loader
        if (isset(self::$loaders[$module])) {
            return (self::$loaders[$module])($module);
        }

        // 2. Provider
        if (self::$provider !== null && self::$provider->supports($module)) {
            return self::$provider->load($module);
        }

        // 3. XOOPS global config for system module
        if ($module === 'system' && isset($GLOBALS['xoopsConfig']) && is_array($GLOBALS['xoopsConfig'])) {
            return $GLOBALS['xoopsConfig'];
        }

        // 4. File-based fallback
        return self::loadFromFile($module);
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFromFile(string $module): array
    {
        // Prevent path traversal attacks
        if (preg_match('/[\/\\\\]|\.\./', $module)) {
            return [];
        }

        $candidates = [];

        if (defined('XOOPS_VAR_PATH')) {
            $candidates[] = XOOPS_VAR_PATH . "/configs/{$module}.php";
        }

        if (defined('XOOPS_ROOT_PATH')) {
            $candidates[] = XOOPS_ROOT_PATH . "/modules/{$module}/config.php";
        }

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $config = include $path;

                if (is_array($config)) {
                    return $config;
                }
            }
        }

        return [];
    }

    private static function extractModule(string $key): string
    {
        $dotPos = strpos($key, '.');

        return $dotPos !== false ? substr($key, 0, $dotPos) : $key;
    }

    private static function invalidateCache(string $module): void
    {
        self::$cache?->forget("config.{$module}");
    }
}
