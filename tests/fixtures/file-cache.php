<?php

declare(strict_types=1);

/**
 * Runs XoopsCacheAdapter operations without the test bootstrap, so XOOPS_VAR_PATH
 * stays undefined and the adapter takes its temp-directory file fallback.
 *
 * Usage: php file-cache.php '[["set","k","v"],["get","k"]]'
 * Each operation uses a new adapter instance; the results print as a JSON list.
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Xoops\Helpers\Provider\XoopsCacheAdapter;

$results = [];

foreach (json_decode($argv[1], true, 8, JSON_THROW_ON_ERROR) as $entry) {
    [$op, $key, $value] = $entry + [2 => null];
    $cache = new XoopsCacheAdapter();
    $results[] = match ($op) {
        'set' => $cache->set($key, $value),
        'get' => $cache->get($key),
        'forget' => $cache->forget($key),
    };
}

echo json_encode($results);
