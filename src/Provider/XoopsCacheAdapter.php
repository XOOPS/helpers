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

use Xoops\Helpers\Contracts\CacheInterface;

/**
 * Multi-tier cache adapter with automatic fallback.
 *
 * Tries backends in order of preference:
 * 1. XoopsCache (native XOOPS cache, if available)
 * 2. APCu (if extension loaded and enabled)
 * 3. File-based cache (always available)
 *
 * The file cache stores serialized data with expiration timestamps
 * in XOOPS_VAR_PATH/caches/xmf/ (or system temp directory).
 */
class XoopsCacheAdapter implements CacheInterface
{
    public function __construct(
        private readonly string $prefix = 'xmf_',
    ) {}

    public function get(string $key): mixed
    {
        $prefixed = $this->prefix . $key;

        if (class_exists('XoopsCache', false)) {
            $payload = \XoopsCache::read($prefixed);

            if (
                $payload === false
                || !is_array($payload)
                || !array_key_exists('value', $payload)
                || ($payload['__xmf_hit'] ?? false) !== true
            ) {
                return null;
            }

            return $payload['value'];
        }

        if ($this->apcuAvailable()) {
            $value = apcu_fetch($prefixed, $success);

            return $success ? $value : null;
        }

        return $this->fileGet($prefixed);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $prefixed = $this->prefix . $key;

        if (class_exists('XoopsCache', false)) {
            return \XoopsCache::write($prefixed, ['__xmf_hit' => true, 'value' => $value], $ttl);
        }

        if ($this->apcuAvailable()) {
            return apcu_store($prefixed, $value, $ttl);
        }

        return $this->fileSet($prefixed, $value, $ttl);
    }

    public function forget(string $key): bool
    {
        $prefixed = $this->prefix . $key;

        if (class_exists('XoopsCache', false)) {
            return \XoopsCache::delete($prefixed);
        }

        if ($this->apcuAvailable()) {
            return apcu_delete($prefixed);
        }

        return $this->fileForget($prefixed);
    }

    public function flush(): bool
    {
        if (class_exists('XoopsCache', false)) {
            return \XoopsCache::clear();
        }

        if ($this->apcuAvailable()) {
            return apcu_clear_cache();
        }

        return $this->fileFlush();
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function many(array $keys, mixed $default = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $value = $this->get($key);
            $result[$key] = $value ?? $default;
        }

        return $result;
    }

    public function putMany(array $values, int $ttl = 3600): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    // ── File cache backend ─────────────────────────────────

    private function fileGet(string $key): mixed
    {
        $file = $this->cacheFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);

        if ($content === false) {
            return null;
        }

        // File backend only supports scalar/array payloads — disallow object instantiation
        $data = @unserialize($content, ['allowed_classes' => false]);

        if (!is_array($data) || !array_key_exists('value', $data)) {
            return null;
        }

        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($file);

            return null;
        }

        return $data['value'];
    }

    private function fileSet(string $key, mixed $value, int $ttl): bool
    {
        $file = $this->cacheFilePath($key);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $data = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];

        return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    private function fileForget(string $key): bool
    {
        $file = $this->cacheFilePath($key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    private function fileFlush(): bool
    {
        $dir = $this->cacheDir();

        if (!is_dir($dir)) {
            return true;
        }

        $files = glob($dir . '/*.cache');

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    private function cacheDir(): string
    {
        if (defined('XOOPS_VAR_PATH')) {
            return XOOPS_VAR_PATH . '/caches/xmf';
        }

        return sys_get_temp_dir() . '/xmf_cache';
    }

    private function cacheFilePath(string $key): string
    {
        return $this->cacheDir() . '/' . md5($key) . '.cache';
    }

    private function apcuAvailable(): bool
    {
        return extension_loaded('apcu') && function_exists('apcu_enabled') && apcu_enabled();
    }
}
