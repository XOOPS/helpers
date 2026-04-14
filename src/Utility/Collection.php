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
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Fluent collection wrapper for array data.
 *
 * Provides a chainable, immutable-style API for common
 * array operations. Transformation methods return new
 * Collection instances.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class Collection implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable
{
    /**
     * @param array<TKey, TValue> $items
     */
    public function __construct(
        private array $items = [],
    ) {}

    /**
     * Create a new collection from items.
     *
     * @template T
     * @param T|array<T> $items
     * @return self<array-key, T>
     */
    public static function make(mixed $items = []): self
    {
        if ($items instanceof self) {
            return new self($items->all());
        }

        return new self(Arr::wrap($items));
    }

    /**
     * Create a collection from a range of numbers.
     *
     * @return self<int, int>
     */
    public static function range(int $from, int $to, int $step = 1): self
    {
        return new self(range($from, $to, $step));
    }

    /**
     * Get all items as a plain array.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get a value by key using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Set a value by key using dot notation (returns new collection).
     *
     * @return self<TKey, TValue>
     */
    public function set(string $key, mixed $value): self
    {
        $items = $this->items;
        Arr::set($items, $key, $value);

        return new self($items);
    }

    /**
     * Check if a key exists.
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Apply a callback to each item and return a new collection.
     *
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $callback
     * @return self<TKey, TNewValue>
     */
    public function map(callable $callback): self
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        return new self($results);
    }

    /**
     * Filter items using a callback and return a new collection.
     *
     * @param callable(TValue, TKey): bool $callback
     * @return self<TKey, TValue>
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Reject items that match the callback.
     *
     * @param callable(TValue, TKey): bool $callback
     * @return self<TKey, TValue>
     */
    public function reject(callable $callback): self
    {
        return $this->filter(static fn($value, $key) => !$callback($value, $key));
    }

    /**
     * Execute a callback on each item (for side effects).
     *
     * @param callable(TValue, TKey): (bool|void) $callback
     * @return self<TKey, TValue>
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Reduce the collection to a single value.
     *
     * @template TResult
     * @param callable(TResult, TValue, TKey): TResult $callback
     * @param TResult $initial
     * @return TResult
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;

        foreach ($this->items as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    /**
     * Extract values by key from nested items.
     *
     * @return self<array-key, mixed>
     */
    public function pluck(string $valueKey, ?string $keyKey = null): self
    {
        return new self(Arr::pluck($this->items, $valueKey, $keyKey));
    }

    /**
     * Group items by a key or callback.
     *
     * @param string|callable(TValue, TKey): array-key $groupBy
     * @return self<array-key, array<TValue>>
     */
    public function groupBy(string|callable $groupBy): self
    {
        return new self(Arr::groupBy($this->items, $groupBy));
    }

    /**
     * Sort items by a key or callback.
     *
     * @param string|callable $callback
     * @return self<TKey, TValue>
     */
    public function sortBy(string|callable $callback, bool $descending = false): self
    {
        $items = $this->items;
        Arr::sortBy($items, $callback, SORT_REGULAR, $descending);

        return new self($items);
    }

    /**
     * Sort items by a key or callback in descending order.
     *
     * @return self<TKey, TValue>
     */
    public function sortByDesc(string|callable $callback): self
    {
        return $this->sortBy($callback, descending: true);
    }

    /**
     * Get the first item, optionally matching a callback.
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Get the last item, optionally matching a callback.
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Flatten the collection to a single level.
     *
     * @return self<int, mixed>
     */
    public function flatten(int $depth = PHP_INT_MAX): self
    {
        return new self(Arr::flatten($this->items, $depth));
    }

    /**
     * Get unique items.
     *
     * @return self<TKey, TValue>
     */
    public function unique(): self
    {
        return new self(array_unique($this->items, SORT_REGULAR));
    }

    /**
     * Get only the values (re-indexed).
     *
     * @return self<int, TValue>
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * Get only the keys.
     *
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    /**
     * Chunk the collection into smaller collections.
     *
     * @return self<int, self<TKey, TValue>>
     */
    public function chunk(int $size): self
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * Take the first N items (or last N if negative).
     *
     * @return self<TKey, TValue>
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return new self(array_slice($this->items, $limit));
        }

        return new self(array_slice($this->items, 0, $limit));
    }

    /**
     * Skip the first N items.
     *
     * @return self<TKey, TValue>
     */
    public function skip(int $count): self
    {
        return new self(array_slice($this->items, $count));
    }

    /**
     * Get a subset with only the specified keys.
     *
     * @param string|array<array-key> $keys
     * @return self<TKey, TValue>
     */
    public function only(string|array $keys): self
    {
        return new self(Arr::only($this->items, $keys));
    }

    /**
     * Get a subset excluding the specified keys.
     *
     * @param string|array<array-key> $keys
     * @return self<TKey, TValue>
     */
    public function except(string|array $keys): self
    {
        return new self(Arr::except($this->items, $keys));
    }

    /**
     * Conditionally apply a callback.
     *
     * @return self<TKey, TValue>
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): self
    {
        if (Value::filled($value)) {
            return $callback($this, $value);
        }

        if ($default !== null) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * Apply a callback for side effects, return self.
     *
     * @return self<TKey, TValue>
     */
    public function tap(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    /**
     * Pass the collection through a callback and return the result.
     */
    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Get the sum of items or a key within items.
     */
    public function sum(?string $key = null): int|float
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        return array_sum(Arr::pluck($this->items, $key));
    }

    /**
     * Get the average value.
     */
    public function avg(?string $key = null): int|float
    {
        $count = $this->count();

        return $count > 0 ? $this->sum($key) / $count : 0;
    }

    /**
     * Get the minimum value.
     */
    public function min(?string $key = null): mixed
    {
        $values = $key !== null ? Arr::pluck($this->items, $key) : $this->items;

        return $values !== [] ? min($values) : null;
    }

    /**
     * Get the maximum value.
     */
    public function max(?string $key = null): mixed
    {
        $values = $key !== null ? Arr::pluck($this->items, $key) : $this->items;

        return $values !== [] ? max($values) : null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return array_map(
            static fn(mixed $value) => $value instanceof self ? $value->toArray() : Data::toArray($value),
            $this->items,
        );
    }

    public function toJson(int $options = 0): string
    {
        return (string) json_encode($this->jsonSerialize(), $options);
    }

    // ── Interface implementations ──────────────────────────

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
