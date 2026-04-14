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

use ArrayAccess;

/**
 * Array utility helpers with dot-notation support.
 *
 * Provides fluent array access, manipulation, and transformation
 * methods using dot notation for nested key paths.
 */
final class Arr
{
    /**
     * Get a value from a nested array/object using dot notation.
     *
     * @param mixed       $target  Array, ArrayAccess, or object
     * @param string|null $key     Dot-notated key path
     * @param mixed       $default Default value or Closure
     */
    public static function get(mixed $target, ?string $key, mixed $default = null): mixed
    {
        if ($key === null || $key === '') {
            return $target;
        }

        if (is_array($target) && array_key_exists($key, $target)) {
            return $target[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess && isset($target[$segment])) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return Value::value($default);
            }
        }

        return $target;
    }

    /**
     * Check if one or more keys exist using dot notation.
     *
     * @param mixed                $target Array, ArrayAccess, or object
     * @param string|array<string> $keys   Key(s) to check
     */
    public static function has(mixed $target, string|array $keys): bool
    {
        $keys = (array) $keys;

        if ($keys === []) {
            return false;
        }

        $sentinel = Value::missing();

        foreach ($keys as $key) {
            $result = self::get($target, $key, $sentinel);
            if ($result instanceof MissingValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set a value in a nested array using dot notation.
     *
     * @param array<string, mixed> $array Array to modify (by reference)
     * @param string               $key   Dot-notated key path
     * @param mixed                $value Value to set
     * @return array<string, mixed>
     */
    public static function set(array &$array, string $key, mixed $value): array
    {
        if ($key === '') {
            return $array = (array) $value;
        }

        $segments = explode('.', $key);
        $current = &$array;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                break;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current[end($segments)] = $value;

        return $array;
    }

    /**
     * Remove one or more keys from an array using dot notation.
     *
     * @param array<string, mixed> $array Array to modify (by reference)
     * @param string|array<string> $keys  Key(s) to remove
     */
    public static function forget(array &$array, string|array $keys): void
    {
        foreach ((array) $keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }

            $segments = explode('.', $key);
            $lastSegment = array_pop($segments);
            $target = &$array;

            foreach ($segments as $segment) {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    continue 2;
                }
                $target = &$target[$segment];
            }

            unset($target[$lastSegment]);
        }
    }

    /**
     * Extract a list of values from a nested array.
     *
     * @param iterable<array-key, mixed> $array    Source data
     * @param string          $valueKey Key to extract as value
     * @param string|null     $keyKey   Optional key to use as array key
     * @return array<array-key, mixed>
     */
    public static function pluck(iterable $array, string $valueKey, ?string $keyKey = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = self::get($item, $valueKey);

            if ($keyKey === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = self::get($item, $keyKey);

                if (is_int($itemKey) || is_string($itemKey)) {
                    $results[$itemKey] = $itemValue;
                } else {
                    $results[] = $itemValue;
                }
            }
        }

        return $results;
    }

    /**
     * Return a subset of the array with only the specified keys.
     *
     * @param array<string, mixed> $array Source array
     * @param string|array<string> $keys  Keys to keep
     * @return array<string, mixed>
     */
    public static function only(array $array, string|array $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Return the array without the specified keys.
     *
     * @param array<string, mixed> $array Source array
     * @param string|array<string> $keys  Keys to exclude
     * @return array<string, mixed>
     */
    public static function except(array $array, string|array $keys): array
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    /**
     * Flatten a multi-dimensional array to a single level.
     *
     * @param array<mixed> $array Source array
     * @param int          $depth Maximum depth to flatten (default: infinite)
     * @return array<int, mixed>
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, self::flatten($item, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * Sort an array by a key or callback.
     *
     * @param array<mixed>    $array      Array to sort (by reference)
     * @param string|callable $callback   Key name or comparator callback
     * @param int             $options    Sort flags (SORT_REGULAR, etc.)
     * @param bool            $descending Sort in descending order
     * @return array<mixed>
     */
    public static function sortBy(array &$array, string|callable $callback, int $options = SORT_REGULAR, bool $descending = false): array
    {
        $results = [];
        $retriever = self::valueRetriever($callback);

        foreach ($array as $key => $value) {
            $results[$key] = $retriever($value, $key);
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        foreach (array_keys($results) as $key) {
            $results[$key] = $array[$key];
        }

        return $array = $results;
    }

    /**
     * Group array items by a key or callback.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array   Source array
     * @param string|callable(TValue, TKey): array-key $groupBy Key name or grouping callback
     * @return array<array-key, array<TValue>>
     */
    public static function groupBy(array $array, string|callable $groupBy): array
    {
        $retriever = self::valueRetriever($groupBy);
        $result = [];

        foreach ($array as $key => $value) {
            $groupKey = $retriever($value, $key);
            $result[$groupKey][] = $value;
        }

        return $result;
    }

    /**
     * Convert a multi-dimensional array to dot notation.
     *
     * @param array<string, mixed> $array   Source array
     * @param string               $prepend Key prefix
     * @return array<string, mixed>
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && $value !== []) {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a dot-notated array back to multi-dimensional.
     *
     * @param array<string, mixed> $array Dot-notated array
     * @return array<string, mixed>
     */
    public static function undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            self::set($results, (string) $key, $value);
        }

        return $results;
    }

    /**
     * Wrap a value in an array if it isn't already one.
     *
     * @return array<array-key, mixed>
     */
    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the first element of an array.
     *
     * @param array<array-key, mixed> $array
     */
    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($array === []) {
                return Value::value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return Value::value($default);
    }

    /**
     * Return the last element of an array.
     *
     * @param array<array-key, mixed> $array
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $array === [] ? Value::value($default) : end($array);
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Filter array items where a key matches a value.
     *
     * Two-argument form: where($array, 'key', $value)     — uses '=' operator
     * Three-argument form: where($array, 'key', '>=', 10) — explicit operator
     *
     * Supports: =, ==, ===, !=, !==, <>, <, >, <=, >=
     *
     * @param array<mixed>  $array             Source array
     * @param string        $key               Key to compare
     * @param mixed         ...$operatorAndValue Operator and value, or just value
     * @return array<mixed>
     */
    public static function where(array $array, string $key, mixed ...$operatorAndValue): array
    {
        if (count($operatorAndValue) === 1) {
            $operator = '=';
            $value = $operatorAndValue[0];
        } else {
            $operator = $operatorAndValue[0] ?? '=';
            $value = $operatorAndValue[1] ?? null;
        }

        return array_filter($array, static function ($item) use ($key, $operator, $value) {
            $retrieved = Arr::get($item, $key);

            return match ($operator) {
                '=', '==' => $retrieved == $value,
                '===' => $retrieved === $value,
                '!=', '<>' => $retrieved != $value,
                '!==' => $retrieved !== $value,
                '<' => $retrieved < $value,
                '>' => $retrieved > $value,
                '<=' => $retrieved <= $value,
                '>=' => $retrieved >= $value,
                default => $retrieved == $value,
            };
        });
    }

    /**
     * Determine if an array is associative (non-sequential keys).
     *
     * @param array<array-key, mixed> $array
     */
    public static function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param iterable<array<mixed>> $array Array of arrays
     * @return array<mixed>
     */
    public static function collapse(iterable $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (is_array($values)) {
                $results = array_merge($results, $values);
            }
        }

        return $results;
    }

    /**
     * Create a value retriever callback from a string key or callable.
     */
    private static function valueRetriever(string|callable $value): callable
    {
        if (is_callable($value)) {
            return $value;
        }

        return static fn(mixed $item) => self::get($item, $value);
    }
}
