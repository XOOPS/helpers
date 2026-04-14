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

// Mock XOOPS constants
if (!defined('XOOPS_ROOT_PATH')) {
    define('XOOPS_ROOT_PATH', sys_get_temp_dir() . '/xoops_test');
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
