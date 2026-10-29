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

/**
 * Test bootstrap — defines mock XOOPS constants so that
 * Provider and Service classes can be tested without a
 * full XOOPS installation.
 */

// Mock XOOPS constants. The root is a fresh private directory per run: a fixed
// name under the shared temp dir could be pre-created by another local account,
// with symlinks that redirect fixture writes. Plain mkdir() fails if it exists.
if (!defined('XOOPS_ROOT_PATH')) {
    $xoopsTestRoot = sys_get_temp_dir() . '/xoops_helpers_test_' . bin2hex(random_bytes(16));
    if (!mkdir($xoopsTestRoot, 0700)) {
        fwrite(STDERR, "Cannot create the private test root {$xoopsTestRoot}\n");
        exit(1);
    }
    define('XOOPS_ROOT_PATH', $xoopsTestRoot);
    register_shutdown_function(static function () use ($xoopsTestRoot): void {
        \Xoops\Helpers\Utility\Filesystem::deleteDirectory($xoopsTestRoot);
    });
    unset($xoopsTestRoot);
}
if (!defined('XOOPS_URL')) {
    define('XOOPS_URL', 'http://localhost');
}
if (!defined('XOOPS_VAR_PATH')) {
    define('XOOPS_VAR_PATH', XOOPS_ROOT_PATH . '/xoops_data');
}
if (!defined('XOOPS_UPLOAD_PATH')) {
    define('XOOPS_UPLOAD_PATH', XOOPS_ROOT_PATH . '/uploads');
}

// Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/stubs/xoops-stubs.php';
