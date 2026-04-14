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

namespace Xoops\Helpers\Utility;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Filesystem utility helpers.
 *
 * Provides file and directory operations including JSON I/O,
 * MIME detection, recursive operations, and zip archiving.
 */
final class Filesystem
{
    /**
     * Read a file in chunks, calling a callback for each chunk.
     *
     * Return false from the callback to stop reading.
     *
     * @param string   $path      File path
     * @param int      $chunkSize Bytes per chunk
     * @param callable $callback  Receives chunk string; return false to stop
     */
    public static function readChunked(string $path, int $chunkSize, callable $callback): bool
    {
        if ($chunkSize < 1) {
            return false;
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $ok = true;

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);

                if ($chunk === false) {
                    $ok = false;
                    break;
                }

                if ($callback($chunk) === false) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return $ok;
    }

    /**
     * Read and decode a JSON file.
     *
     * @return array<string, mixed>|null Decoded data or null on failure
     */
    public static function readJson(string $path): ?array
    {
        $json = @file_get_contents($path);

        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Encode data and write it to a JSON file.
     *
     * @param string $path  File path
     * @param mixed  $data  Data to encode
     * @param int    $flags JSON encoding flags
     */
    public static function putJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): bool
    {
        $json = json_encode($data, $flags);

        if ($json === false) {
            return false;
        }

        return @file_put_contents($path, $json . "\n") !== false;
    }

    /**
     * Detect the MIME type of a file using finfo.
     */
    public static function mimeType(string $path): ?string
    {
        if (!file_exists($path) || !function_exists('finfo_open')) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime !== false ? $mime : null;
    }

    /**
     * Get the file extension (lowercase).
     */
    public static function extension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Check if a file is an image by extension or MIME type.
     */
    public static function isImage(string $filename): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'webp', 'avif', 'svg'];

        if (in_array(self::extension($filename), $imageExtensions, true)) {
            return true;
        }

        $mime = self::mimeType($filename);

        return is_string($mime) && str_starts_with($mime, 'image/');
    }

    /**
     * Check if a directory and all its contents are writable.
     */
    public static function isWritableRecursive(string $directory): bool
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            return false;
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                if (!self::isWritableRecursive($path)) {
                    return false;
                }
            } elseif (!is_writable($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a directory, optionally recursively.
     */
    public static function mkdir(string $directory, int $mode = 0775, bool $recursive = true): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        return @mkdir($directory, $mode, $recursive);
    }

    /**
     * Recursively delete a directory and all its contents.
     */
    public static function deleteDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            // Remove symlinks as files — never follow into external trees
            if (is_link($path)) {
                if (!@unlink($path)) {
                    return false;
                }
            } elseif (is_dir($path)) {
                if (!self::deleteDirectory($path)) {
                    return false;
                }
            } elseif (!@unlink($path)) {
                return false;
            }
        }

        return @rmdir($directory);
    }

    /**
     * Recursively copy a directory.
     */
    public static function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        self::mkdir($destination);

        $entries = scandir($source);

        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $srcPath = $source . DIRECTORY_SEPARATOR . $entry;
            $dstPath = $destination . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($srcPath)) {
                if (!self::copyDirectory($srcPath, $dstPath)) {
                    return false;
                }
            } elseif (!@copy($srcPath, $dstPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Move (copy then delete) a directory.
     */
    public static function moveDirectory(string $source, string $destination): bool
    {
        return self::copyDirectory($source, $destination) && self::deleteDirectory($source);
    }

    /**
     * Create a zip archive from a directory.
     *
     * @param string          $directory Source directory
     * @param string          $zipPath   Output zip file path
     * @param array<string>|null $include   Glob patterns to include (null = all)
     * @param array<string>|null $exclude   Glob patterns to exclude (null = none)
     */
    public static function zip(string $directory, string $zipPath, ?array $include = null, ?array $exclude = null): bool
    {
        if (!is_dir($directory) || !class_exists(ZipArchive::class)) {
            return false;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        $basePath = realpath($directory) ?: $directory;

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $realPath = $file->getRealPath();

            if ($realPath === false) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', substr($realPath, strlen($basePath))), '/');

            if (!self::matchesGlobs($relativePath, $include, $exclude)) {
                continue;
            }

            $zip->addFile($realPath, $relativePath);
        }

        return $zip->close();
    }

    /**
     * Extract a zip archive to a directory.
     */
    public static function unzip(string $zipPath, string $destination): bool
    {
        if (!class_exists(ZipArchive::class)) {
            return false;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return false;
        }

        self::mkdir($destination);
        $realDest = realpath($destination);

        if ($realDest === false) {
            $zip->close();

            return false;
        }

        $realDest = rtrim($realDest, '/\\') . DIRECTORY_SEPARATOR;

        // Validate each entry to prevent Zip Slip (path traversal)
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if ($entryName === false) {
                continue;
            }

            // Reject entries with traversal sequences or absolute paths before any filesystem operation
            $normalized = str_replace('\\', '/', $entryName);

            if (
                str_starts_with($normalized, '/')
                || str_contains($normalized, '../')
                || preg_match('/^[A-Za-z]:/', $normalized)
            ) {
                $zip->close();

                return false;
            }
        }

        $result = $zip->extractTo($destination);
        $zip->close();

        return $result;
    }

    /**
     * Get the size of a file in bytes.
     */
    public static function size(string $path): int|false
    {
        return @filesize($path);
    }

    /**
     * Check if a relative path matches include/exclude glob patterns.
     *
     * @param string          $relativePath Path to check
     * @param array<string>|null $include      Include patterns (null = match all)
     * @param array<string>|null $exclude      Exclude patterns (null = exclude none)
     */
    private static function matchesGlobs(string $relativePath, ?array $include, ?array $exclude): bool
    {
        $relativePath = ltrim($relativePath, '/');

        if ($include !== null && $include !== []) {
            $matched = false;

            foreach ($include as $pattern) {
                if (fnmatch($pattern, $relativePath, FNM_PATHNAME | FNM_CASEFOLD)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return false;
            }
        }

        if ($exclude !== null) {
            foreach ($exclude as $pattern) {
                if (fnmatch($pattern, $relativePath, FNM_PATHNAME | FNM_CASEFOLD)) {
                    return false;
                }
            }
        }

        return true;
    }
}
