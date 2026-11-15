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
 * in XOOPS_VAR_PATH/caches/xmf/ (or a per-user system temp directory).
 *
 * Cache scalars and arrays. On the XoopsCache and file backends an object comes
 * back as __PHP_Incomplete_Class, because cached data is unserialized without
 * classes; only the APCu backend returns objects intact.
 */
class XoopsCacheAdapter implements CacheInterface
{
    /** Starts every native-cache entry this adapter writes; base64(serialize($value)) follows. */
    private const NATIVE_TAG = 'xmf1:';

    public function __construct(
        private readonly string $prefix = 'xmf_',
    ) {}

    public function get(string $key): mixed
    {
        $prefixed = $this->prefix . $key;

        if (class_exists('XoopsCache', false)) {
            return $this->decodeNative(\XoopsCache::read($prefixed));
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
            return \XoopsCache::write($prefixed, self::NATIVE_TAG . base64_encode(serialize($value)), $ttl);
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

    /**
     * Clears the whole backing store, not only this adapter's prefixed keys: the
     * shared XoopsCache or APCu cache, or the fallback cache directory.
     */
    public function flush(): bool
    {
        if (class_exists('XoopsCache', false)) {
            // clear() is an instance method on the core class; read/write/delete are static.
            return \XoopsCache::getInstance()->clear();
        }

        if ($this->apcuAvailable()) {
            return apcu_clear_cache();
        }

        return $this->fileFlush();
    }

    /**
     * The core file engine runs stripslashes() over every element of a cached
     * array, turning each scalar into a string, so the value travels as one
     * base64 string, which every engine returns unchanged. Anything else (another
     * writer's entry, the array shape of 1.1.1 and earlier, damaged data) is a miss.
     */
    private function decodeNative(mixed $payload): mixed
    {
        if (!is_string($payload) || !str_starts_with($payload, self::NATIVE_TAG)) {
            return null;
        }

        $serialized = base64_decode(substr($payload, strlen(self::NATIVE_TAG)), true);

        if ($serialized === false) {
            return null;
        }

        // Same rule as the file fallback: cached data never instantiates objects. A
        // damaged entry is a miss, not a warning on the page, and so is any entry
        // unserialize() complains about, because it can still return a value then
        // (an integer clamped to PHP_INT_MAX, a prefix with trailing bytes ignored).
        $damaged = false;
        set_error_handler(static function () use (&$damaged): bool {
            $damaged = true;

            return true;
        }, E_WARNING | E_NOTICE);

        try {
            $value = unserialize($serialized, ['allowed_classes' => false]);
        } finally {
            restore_error_handler();
        }

        if ($damaged || ($value === false && $serialized !== serialize(false))) {
            return null;
        }

        return $value;
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
        $dir = $this->cacheDir();

        if ($dir === null) {
            return null;
        }

        $file = $this->cacheFilePath($dir, $key);

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
        $dir = $this->cacheDir();

        if ($dir === null) {
            return false;
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = serialize([
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ]);

        // Write a freshly, exclusively created file and rename it over the entry:
        // rename() replaces whatever sits at the entry name, a symlink included,
        // instead of writing through it the way file_put_contents() would.
        $tmp = $dir . '/' . bin2hex(random_bytes(8)) . '.tmp';
        $handle = @fopen($tmp, 'xb');

        if ($handle === false) {
            return false;
        }

        $written = fwrite($handle, $payload) === strlen($payload);

        if (fclose($handle) && $written && @rename($tmp, $this->cacheFilePath($dir, $key))) {
            return true;
        }

        @unlink($tmp);

        return false;
    }

    private function fileForget(string $key): bool
    {
        $dir = $this->cacheDir();

        if ($dir === null) {
            return true;
        }

        $file = $this->cacheFilePath($dir, $key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    private function fileFlush(): bool
    {
        $dir = $this->cacheDir();

        if ($dir === null || !is_dir($dir)) {
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

    /**
     * Cache directory, or null when no trustworthy one is available (run uncached).
     */
    private function cacheDir(): ?string
    {
        if (defined('XOOPS_VAR_PATH')) {
            return XOOPS_VAR_PATH . '/caches/xmf';
        }

        // On Windows %TEMP% is normally per-account, and planting a symlink needs a
        // privilege that ordinary accounts do not hold.
        if (DIRECTORY_SEPARATOR === '\\') {
            return sys_get_temp_dir() . '/xmf_cache';
        }

        // The POSIX temp dir is shared by every local account. Use a per-user
        // directory and refuse it when it is a link or another account owns it:
        // its owner could plant entry symlinks there. Without ext-posix the owner
        // cannot be checked, so the file cache stays off.
        if (!function_exists('posix_geteuid')) {
            return null;
        }

        $uid = posix_geteuid();
        $dir = sys_get_temp_dir() . '/xmf_cache_' . $uid;

        if (!@mkdir($dir, 0700) && !is_dir($dir)) {
            return null;
        }

        $stat = @lstat($dir);

        // Must be a real directory, ours, and closed to group/other (0700).
        if (
            $stat === false
            || ($stat['mode'] & 0170000) !== 0040000
            || $stat['uid'] !== $uid
            || ($stat['mode'] & 0077) !== 0
        ) {
            return null;
        }

        return $dir;
    }

    private function cacheFilePath(string $dir, string $key): string
    {
        return $dir . '/' . hash('sha256', $key) . '.cache';
    }

    private function apcuAvailable(): bool
    {
        return extension_loaded('apcu') && function_exists('apcu_enabled') && apcu_enabled();
    }
}
