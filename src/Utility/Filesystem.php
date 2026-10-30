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
    /** Default cap on the total uncompressed size unzip() extracts (1 GiB). */
    public const UNZIP_MAX_BYTES = 1024 ** 3;

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

                // fread() can return '' at EOF before feof() flips — don't emit
                // a trailing empty chunk to the callback.
                if ($chunk === '') {
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

        // Contract is an object (associative array), per the @return type — a
        // bare JSON list (e.g. "[1,2,3]") is not a valid config object.
        return is_array($data) && !array_is_list($data) ? $data : null;
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
     * Copy a single file, creating the destination directory if needed (H2).
     *
     * For directories use copyDirectory().
     */
    public static function copy(string $source, string $destination): bool
    {
        if (!is_file($source)) {
            return false;
        }

        $dir = \dirname($destination);

        if (!is_dir($dir) && !self::mkdir($dir)) {
            return false;
        }

        return @copy($source, $destination);
    }

    /**
     * Delete a single file or symlink (H2). Returns true if already absent.
     *
     * Refuses real directories — use deleteDirectory() for those.
     */
    public static function delete(string $path): bool
    {
        if (!file_exists($path) && !is_link($path)) {
            return true;
        }

        if (is_dir($path) && !is_link($path)) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * Move/rename a single file, creating the destination directory if needed (H2).
     *
     * Falls back to copy-then-delete when rename() cannot cross filesystems
     * (e.g. moving between devices/mount points), mirroring moveDirectory().
     * For directories use moveDirectory().
     */
    public static function move(string $source, string $destination): bool
    {
        if (!is_file($source)) {
            return false;
        }

        $dir = \dirname($destination);

        if (!is_dir($dir) && !self::mkdir($dir)) {
            return false;
        }

        if (@rename($source, $destination)) {
            return true;
        }

        // Cross-filesystem fallback: copy then remove the source.
        if (@copy($source, $destination)) {
            return @unlink($source);
        }

        return false;
    }

    /**
     * Alias of move() for call sites that read more naturally as a rename (H2).
     */
    public static function rename(string $source, string $destination): bool
    {
        return self::move($source, $destination);
    }

    /**
     * Create a directory and drop an anti-listing index.html guard (H7).
     *
     * Matches the XOOPS convention: the guard contains the JS history-back
     * redirect that modules currently write by hand after each mkdir.
     */
    public static function secureDir(string $directory, int $mode = 0775): bool
    {
        if (!self::mkdir($directory, $mode)) {
            return false;
        }

        $guard = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . 'index.html';

        if (!is_file($guard) && @file_put_contents($guard, '<script>history.go(-1);</script>') === false) {
            return false;
        }

        return true;
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
     *
     * Directory links are recreated as links with the same target, never descended into,
     * so a link cycle cannot multiply the copy. A destination that resolves to the source
     * or inside it is refused (false, nothing written), as the copy would walk its own output.
     * A link already inside the destination is never written through (false), except a
     * directory link identical to the one being copied, so a repeated copy succeeds.
     *
     * The checks run before the writes they guard: do not copy from or into a tree that an
     * untrusted party can modify concurrently. Copy a snapshot into a fresh private
     * directory instead.
     */
    public static function copyDirectory(string $source, string $destination): bool
    {
        // Checked on the paths as given: realpath('') is the working directory.
        if (!is_dir($source) || $destination === '') {
            return false;
        }

        $realSource = realpath($source);
        $realDest = self::resolvePath($destination);

        if ($realSource === false || $realDest === null) {
            return false;
        }

        $separators = DIRECTORY_SEPARATOR === '\\' ? '/\\' : '/';
        $prefix = self::comparablePath(rtrim($realSource, $separators)) . '/';

        if (str_starts_with(self::comparablePath(rtrim($realDest, $separators)) . '/', $prefix)) {
            return false;
        }

        // An alias no path check sees (a bind mount, a UNC path to a local folder) is caught
        // by identity: no existing ancestor of the destination may be the source itself.
        $sourceId = self::identity($realSource);

        $path = $realDest;

        while ($sourceId !== null) {
            if (file_exists($path) && self::identity($path) === $sourceId) {
                return false;
            }

            $parent = dirname($path);

            if ($parent === $path) {
                break;
            }

            $path = $parent;
        }

        $sourceIds = [];

        // A destination that does not exist yet holds no file that could alias a source file.
        if (!self::collectSourceIds($realSource, $sourceIds, file_exists($realDest))) {
            return false;
        }

        // The checked paths are the ones used: mkdir() would apply a ".." in $destination
        // lexically ("src/link/../out" makes src/out) where the check followed the link.
        return self::copyTree($realSource, $realDest, $sourceIds);
    }

    /**
     * True for a link, and for any entry that resolves somewhere other than where it sits
     * in a canonical parent: a Windows junction is neither is_link() nor is_dir() to PHP.
     */
    private static function isLinked(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }

        $real = realpath($path);

        return $real !== false && self::comparablePath($real) !== self::comparablePath($path);
    }

    /**
     * "dev:ino" of a path, or null when it cannot be read or the filesystem reports inode 0
     * (no identity to compare, so the identity guards sit out there).
     */
    private static function identity(string $path): ?string
    {
        $stat = @stat($path);

        return $stat === false || $stat['ino'] === 0 ? null : $stat['dev'] . ':' . $stat['ino'];
    }

    /**
     * Collect the identity of every directory copyTree() will read and, with $withFiles,
     * of every file it will read (through a file link, the target it reads). Directory
     * links and junctions are skipped as copyTree() skips them. False when a directory
     * contains itself further down (a bind mount of an ancestor), which the copy would
     * follow without end.
     *
     * @param array<string, true> $ids
     * @param array<string, true> $ancestors
     */
    private static function collectSourceIds(string $directory, array &$ids, bool $withFiles, array $ancestors = []): bool
    {
        $id = self::identity($directory);

        if ($id !== null) {
            if (isset($ancestors[$id])) {
                return false;
            }

            $ids[$id] = $ancestors[$id] = true;
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = rtrim($directory, DIRECTORY_SEPARATOR === '\\' ? '/\\' : '/') . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                if (!self::isLinked($path) && !self::collectSourceIds($path, $ids, $withFiles, $ancestors)) {
                    return false;
                }
            } elseif ($withFiles && ($fileId = self::identity($path)) !== null) {
                $ids[$fileId] = true;
            }
        }

        return true;
    }

    /**
     * Identities catch what paths cannot (bind mounts): a destination directory that is any
     * source directory would overwrite the source, and a source directory that is one the
     * copy created would walk the output.
     *
     * @param array<string, true> $sourceIds identities of every source directory
     * @param array<string, true> $created   identities of every destination directory so far
     */
    private static function copyTree(string $source, string $destination, array $sourceIds, array &$created = []): bool
    {
        $separators = DIRECTORY_SEPARATOR === '\\' ? '/\\' : '/';
        $sourceId = self::identity($source);

        if ($sourceId !== null && isset($created[$sourceId])) {
            return false;
        }

        if (!self::mkdir($destination) || !is_dir($destination)) {
            return false;
        }

        $destinationId = self::identity($destination);

        if ($destinationId !== null) {
            if (isset($sourceIds[$destinationId])) {
                return false;
            }

            $created[$destinationId] = true;
        }

        $entries = scandir($source);

        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            // Trimmed so a root ("C:\", "/") does not double the separator isLinked() compares.
            $srcPath = rtrim($source, $separators) . DIRECTORY_SEPARATOR . $entry;
            $dstPath = rtrim($destination, $separators) . DIRECTORY_SEPARATOR . $entry;

            if (is_link($srcPath) && is_dir($srcPath)) {
                $copied = self::copyDirectoryLink($srcPath, $dstPath);
            } elseif (!is_link($srcPath) && self::isLinked($srcPath)) {
                // A junction cannot be recreated portably, and following it could loop.
                $copied = false;
            } elseif (self::isLinked($dstPath)) {
                // An existing link could lead back into the source or anywhere else.
                $copied = false;
            } elseif (is_dir($srcPath)) {
                $copied = self::copyTree($srcPath, $dstPath, $sourceIds, $created);
            } else {
                $copied = self::copyFileInto($srcPath, $dstPath, $sourceIds);
            }

            if (!$copied) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recreate a directory link as a link with the same target.
     */
    private static function copyDirectoryLink(string $srcPath, string $dstPath): bool
    {
        $target = readlink($srcPath);

        if ($target === false) {
            return false;
        }

        // An identical link from an earlier copy is kept; a symlink() listed in
        // disable_functions throws rather than returning false.
        return is_link($dstPath)
            ? readlink($dstPath) === $target
            : function_exists('symlink') && @symlink($target, $dstPath);
    }

    /**
     * copy() writes into an existing file, keeping its owner, mode and ACL. One with other
     * names (a hard link) or that is a source file (a file bind mount) is refused, as the
     * write would change those too.
     *
     * @param array<string, true> $sourceIds
     */
    private static function copyFileInto(string $srcPath, string $dstPath, array $sourceIds): bool
    {
        $stat = is_file($dstPath) ? @stat($dstPath) : false;

        if ($stat !== false && ($stat['nlink'] > 1 || isset($sourceIds[self::identity($dstPath) ?? '']))) {
            return false;
        }

        return @copy($srcPath, $dstPath);
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
     *
     * Entries resolving outside $directory (e.g. escaping symlinks) are skipped, but the
     * check runs before ZipArchive reads each file at close(): do not zip a tree that an
     * untrusted party can modify concurrently — archive a snapshot of it instead.
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

        $basePath = realpath($directory);

        if ($basePath === false) {
            $zip->close();

            return false;
        }

        $windows = DIRECTORY_SEPARATOR === '\\';

        // Strip any trailing separator so the prefix check and substr() below stay
        // correct for a root base path too ("/" or "C:\"); otherwise the guard would
        // build a doubled separator and skip every file, leaving an empty archive.
        $basePath = rtrim($basePath, $windows ? '/\\' : '/');
        $prefix = self::comparablePath($basePath) . '/';

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $realPath = $file->getRealPath();

            if ($realPath === false) {
                continue;
            }

            // Skip anything that resolves outside the base directory (e.g. an escaping
            // symlink); otherwise the relative path below would be wrong/empty and could
            // leak external content into the archive. On Windows the comparison folds
            // separators and case; on POSIX it is a byte-exact prefix check.
            if (!str_starts_with(self::comparablePath($realPath), $prefix)) {
                continue;
            }

            $relativePath = substr($realPath, strlen($basePath) + 1);

            if ($windows) {
                $relativePath = str_replace('\\', '/', $relativePath);
            } elseif (str_contains($relativePath, '\\')) {
                // A literal backslash would read as a separator when the archive is
                // extracted on Windows ("a\..\x"), so such files are left out.
                continue;
            }

            if (!self::matchesGlobs($relativePath, $include, $exclude)) {
                continue;
            }

            $zip->addFile($realPath, $relativePath);
        }

        return $zip->close();
    }

    /**
     * Extract a zip archive to a directory.
     *
     * Refused (false, nothing written) when a member name escapes the destination or
     * when a member would be written through a link already in the destination. The
     * check runs before extraction: do not extract into a directory that an untrusted
     * party can modify concurrently. Extract into a fresh private directory instead.
     *
     * Also refused when the members decompress to more than $maxBytes in total (null: no
     * limit), so a small archive cannot expand into a full disk.
     */
    public static function unzip(string $zipPath, string $destination, ?int $maxBytes = self::UNZIP_MAX_BYTES): bool
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

        // Only the platform's separators are trimmed: on POSIX "out\" is its own directory.
        $windows = DIRECTORY_SEPARATOR === '\\';
        $realDest = rtrim($realDest, $windows ? '/\\' : '/') . DIRECTORY_SEPARATOR;

        $seen = [];

        // Validate each entry to prevent Zip Slip (path traversal)
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if ($entryName === false) {
                continue;
            }

            // Reject entries with traversal sequences or absolute paths before any filesystem operation
            $normalized = str_replace('\\', '/', $entryName);

            if (
                // extractTo() opens members by name, so a repeated name extracts the first
                // member again and again, past what the byte budget counted.
                isset($seen[$entryName])
                || str_starts_with($normalized, '/')
                || str_contains($normalized, '../')
                || preg_match('/^[A-Za-z]:/', $normalized)
                // On Windows extraction drops a "C:" inside the basename too ("sub/C:x" writes sub/x).
                || ($windows && str_contains($normalized, ':'))
                // Walked as written: on POSIX "a\b" is one filename, not "a" then "b".
                || !self::isContainedOutputPath($realDest, $windows ? $normalized : $entryName)
            ) {
                $zip->close();

                return false;
            }

            $seen[$entryName] = true;
        }

        if ($maxBytes !== null && !self::expandsWithin($zip, $maxBytes)) {
            $zip->close();

            return false;
        }

        $result = $zip->extractTo($realDest);
        $zip->close();

        return $result;
    }

    /**
     * Decompresses every member, writing nothing, and stops once more than $maxBytes have
     * come out. The declared sizes are not used: libzip extracts a member whose header
     * understates its size without complaint. Costs a second decompression of the archive.
     */
    private static function expandsWithin(ZipArchive $zip, int $maxBytes): bool
    {
        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stream = $zip->getStreamIndex($i);

            if ($stream === false) {
                return false;
            }

            while (!feof($stream)) {
                $chunk = fread($stream, 65536);

                if ($chunk === false || ($total += strlen($chunk)) > $maxBytes) {
                    fclose($stream);

                    return false;
                }
            }

            fclose($stream);
        }

        return true;
    }

    /**
     * A clean member name can still leave the destination through what already sits in
     * it: "sub" -> /etc carries "sub/passwd" outside, and a dangling "file.txt" link
     * creates its target. Each existing component of the output path must be a non-link
     * that resolves under $realDest (which ends in a separator); the first missing one
     * ends the walk, as extraction creates the rest as plain directories and files.
     */
    private static function isContainedOutputPath(string $realDest, string $member): bool
    {
        $prefix = self::comparablePath($realDest);
        $path = substr($realDest, 0, -1);
        $parts = explode('/', $member);
        $last = count($parts) - 1;

        // ext/zip drops everything up to a directory named "<x>." ("a./b/c" is written
        // as "b/c"), so the walk would check a path that is never written. Checked for every
        // directory up front: the walk below stops at the first one that does not exist.
        foreach ($parts as $i => $part) {
            if ($i < $last && $part !== '.' && str_ends_with($part, '.')) {
                return false;
            }
        }

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            $path .= DIRECTORY_SEPARATOR . $part;

            if (is_link($path)) {
                return false;
            }

            if (!file_exists($path)) {
                return true;
            }

            $real = realpath($path);

            if ($real === false || !str_starts_with(self::comparablePath($real) . '/', $prefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The absolute path $path would have once created: the nearest existing ancestor is
     * resolved by realpath() and the missing rest appended, "." dropped. A ".." can climb
     * back into existing directories, where the next name may be a link
     * ("missing/../link/../x"), so the path after it is resolved afresh from the parent.
     * Each such step removes a "name/.." pair, so the recursion ends. Null when no
     * ancestor resolves.
     */
    private static function resolvePath(string $path): ?string
    {
        $separators = DIRECTORY_SEPARATOR === '\\' ? '/\\' : '/';
        $missing = [];

        while (($real = realpath($path)) === false) {
            $parent = dirname($path);

            if ($parent === $path) {
                return null;
            }

            $missing[] = basename($path);
            $path = $parent;
        }

        $missing = array_reverse($missing);

        foreach ($missing as $i => $part) {
            if ($part === '..') {
                $rest = array_slice($missing, $i + 1);

                return self::resolvePath(
                    $rest === [] ? dirname($real) : rtrim(dirname($real), $separators) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $rest),
                );
            }

            if ($part !== '.' && $part !== '') {
                $real = rtrim($real, $separators) . DIRECTORY_SEPARATOR . $part;
            }
        }

        return $real;
    }

    /**
     * A path in the form containment checks compare. A backslash is a separator only on
     * Windows, where case is folded too; on POSIX it is an ordinary filename character and
     * must never be folded, or "src\secret" (a sibling of src/) would compare as
     * "src/secret" and pass a check for src/.
     */
    private static function comparablePath(string $path): string
    {
        return DIRECTORY_SEPARATOR === '\\' ? strtolower(str_replace('\\', '/', $path)) : $path;
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
