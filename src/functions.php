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
 * XOOPS Helpers — Optional global function wrappers.
 *
 * This file is NOT auto-loaded by default. To use these functions,
 * either require this file explicitly in your module bootstrap:
 *
 *   require_once 'vendor/xoops/helpers/src/functions.php';
 *
 * Or add it to your module's composer.json autoload:
 *
 *   "autoload": { "files": ["vendor/xoops/helpers/src/functions.php"] }
 *
 * All functions are guarded by function_exists() to prevent
 * conflicts with other libraries.
 */

use Xoops\Helpers\Utility\Arr;
use Xoops\Helpers\Utility\Benchmark;
use Xoops\Helpers\Utility\Collection;
use Xoops\Helpers\Utility\Data;
use Xoops\Helpers\Utility\Encoding;
use Xoops\Helpers\Utility\Environment;
use Xoops\Helpers\Utility\HtmlBuilder;
use Xoops\Helpers\Utility\Number;
use Xoops\Helpers\Utility\Optional;
use Xoops\Helpers\Utility\Pipeline;
use Xoops\Helpers\Utility\Retry;
use Xoops\Helpers\Utility\Str;
use Xoops\Helpers\Utility\Stringable;
use Xoops\Helpers\Utility\Tap;
use Xoops\Helpers\Utility\ThrowHelper;
use Xoops\Helpers\Utility\Transform;
use Xoops\Helpers\Utility\Value;

// ── Value helpers ──────────────────────────────────────────

if (!function_exists('value')) {
    function value(mixed $value, mixed ...$args): mixed
    {
        return Value::value($value, ...$args);
    }
}

if (!function_exists('blank')) {
    function blank(mixed $value): bool
    {
        return Value::blank($value);
    }
}

if (!function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return Value::filled($value);
    }
}

if (!function_exists('optional')) {
    function optional(mixed $value): Optional
    {
        return Value::optional($value);
    }
}

// ── Array helpers ──────────────────────────────────────────

if (!function_exists('arr_get')) {
    function arr_get(mixed $target, ?string $key, mixed $default = null): mixed
    {
        return Arr::get($target, $key, $default);
    }
}

if (!function_exists('arr_set')) {
    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    function arr_set(array &$array, string $key, mixed $value): array
    {
        return Arr::set($array, $key, $value);
    }
}

if (!function_exists('arr_has')) {
    /**
     * @param string|array<string> $keys
     */
    function arr_has(mixed $target, string|array $keys): bool
    {
        return Arr::has($target, $keys);
    }
}

if (!function_exists('arr_pluck')) {
    /**
     * @param iterable<array-key, mixed> $array
     * @return array<array-key, mixed>
     */
    function arr_pluck(iterable $array, string $valueKey, ?string $keyKey = null): array
    {
        return Arr::pluck($array, $valueKey, $keyKey);
    }
}

if (!function_exists('arr_only')) {
    /**
     * @param array<string, mixed> $array
     * @param string|array<string> $keys
     * @return array<string, mixed>
     */
    function arr_only(array $array, string|array $keys): array
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('arr_except')) {
    /**
     * @param array<string, mixed> $array
     * @param string|array<string> $keys
     * @return array<string, mixed>
     */
    function arr_except(array $array, string|array $keys): array
    {
        return Arr::except($array, $keys);
    }
}

// ── String helpers ─────────────────────────────────────────

if (!function_exists('str_slug')) {
    function str_slug(string $title, string $separator = '-'): string
    {
        return Str::slug($title, $separator);
    }
}

if (!function_exists('str_limit')) {
    function str_limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return Str::limit($value, $limit, $end);
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 16): string
    {
        return Str::random($length);
    }
}

if (!function_exists('str_camel')) {
    function str_camel(string $value): string
    {
        return Str::camel($value);
    }
}

if (!function_exists('str_snake')) {
    function str_snake(string $value, string $delimiter = '_'): string
    {
        return Str::snake($value, $delimiter);
    }
}

if (!function_exists('str_studly')) {
    function str_studly(string $value): string
    {
        return Str::studly($value);
    }
}

// ── Number helpers ─────────────────────────────────────────

if (!function_exists('number_format_human')) {
    function number_format_human(int|float $number, int $decimals = 1): string
    {
        return Number::forHumans($number, $decimals);
    }
}

if (!function_exists('number_file_size')) {
    function number_file_size(int|float $bytes, int $precision = 2): string
    {
        return Number::fileSize($bytes, $precision);
    }
}

// ── Collection ─────────────────────────────────────────────

if (!function_exists('collect')) {
    /**
     * @return Collection<array-key, mixed>
     */
    function collect(mixed $items = []): Collection
    {
        return Collection::make($items);
    }
}

// ── Fluent string ──────────────────────────────────────────

if (!function_exists('str')) {
    function str(string $value): Stringable
    {
        return Stringable::of($value);
    }
}

// ── Pipeline ───────────────────────────────────────────────

if (!function_exists('pipeline')) {
    function pipeline(mixed $value): Pipeline
    {
        return Pipeline::send($value);
    }
}

// ── Functional helpers ─────────────────────────────────────

if (!function_exists('tap')) {
    function tap(mixed $value, callable $callback): mixed
    {
        return Tap::tap($value, $callback);
    }
}

if (!function_exists('transform')) {
    function transform(mixed $value, callable $callback, mixed $default = null): mixed
    {
        return Transform::transform($value, $callback, $default);
    }
}

if (!function_exists('retry')) {
    function retry(int $times, callable $callback, int|callable $sleepMs = 0, ?callable $when = null): mixed
    {
        return Retry::retry($times, $callback, $sleepMs, $when);
    }
}

if (!function_exists('rescue')) {
    function rescue(callable $callback, mixed $default = null, ?callable $when = null): mixed
    {
        return Retry::rescue($callback, $default, $when);
    }
}

// ── Exception helpers ──────────────────────────────────────

if (!function_exists('throw_if')) {
    function throw_if(bool $condition, string $exceptionClass, mixed ...$args): void
    {
        ThrowHelper::throwIf($condition, $exceptionClass, ...$args);
    }
}

if (!function_exists('throw_unless')) {
    function throw_unless(bool $condition, string $exceptionClass, mixed ...$args): void
    {
        ThrowHelper::throwUnless($condition, $exceptionClass, ...$args);
    }
}

// ── HTML helpers ───────────────────────────────────────────

if (!function_exists('html_attributes')) {
    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    function html_attributes(array $attributes): string
    {
        return HtmlBuilder::attributes($attributes);
    }
}

if (!function_exists('html_classes')) {
    /**
     * @param array<int|string, string|bool> $classes
     */
    function html_classes(array $classes): string
    {
        return HtmlBuilder::classes($classes);
    }
}

// ── Environment ────────────────────────────────────────────

if (!function_exists('env')) {
    function env(string $key, string $default = ''): string
    {
        return Environment::get($key, $default);
    }
}

// ── Benchmark ──────────────────────────────────────────────

if (!function_exists('benchmark')) {
    /**
     * @return array<string, mixed>
     */
    function benchmark(callable $callback): array
    {
        return Benchmark::measure($callback);
    }
}
