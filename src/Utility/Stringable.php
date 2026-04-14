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

use Stringable as NativeStringable;

/**
 * Fluent, chainable string manipulation wrapper.
 *
 * Wraps Str utility methods for chainable operations.
 * Immutable — each operation returns a new Stringable instance.
 *
 * Usage:
 *   $slug = Stringable::of('  Hello World! ')
 *       ->trim()
 *       ->slug('-')
 *       ->toString();  // "hello-world"
 *
 *   $masked = Stringable::of('user@example.com')
 *       ->mask('*', 2, 5)
 *       ->toString();  // "us*****xample.com"
 */
final class Stringable implements NativeStringable
{
    private function __construct(
        private readonly string $value,
    ) {}

    /**
     * Create a new Stringable from a string.
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    // ── Transformations ────────────────────────────────────

    public function trim(string $characters = " \t\n\r\0\x0B"): self
    {
        return new self(trim($this->value, $characters));
    }

    public function ltrim(string $characters = " \t\n\r\0\x0B"): self
    {
        return new self(ltrim($this->value, $characters));
    }

    public function rtrim(string $characters = " \t\n\r\0\x0B"): self
    {
        return new self(rtrim($this->value, $characters));
    }

    public function lower(): self
    {
        return new self(mb_strtolower($this->value, 'UTF-8'));
    }

    public function upper(): self
    {
        return new self(mb_strtoupper($this->value, 'UTF-8'));
    }

    public function ucfirst(): self
    {
        return new self(mb_strtoupper(mb_substr($this->value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($this->value, 1, null, 'UTF-8'));
    }

    public function camel(): self
    {
        return new self(Str::camel($this->value));
    }

    public function snake(string $delimiter = '_'): self
    {
        return new self(Str::snake($this->value, $delimiter));
    }

    public function studly(): self
    {
        return new self(Str::studly($this->value));
    }

    public function kebab(): self
    {
        return new self(Str::kebab($this->value));
    }

    public function slug(string $separator = '-'): self
    {
        return new self(Str::slug($this->value, $separator));
    }

    public function limit(int $limit = 100, string $end = '...'): self
    {
        return new self(Str::limit($this->value, $limit, $end));
    }

    public function replace(string $search, string $replace): self
    {
        return new self(str_replace($search, $replace, $this->value));
    }

    public function replaceFirst(string $search, string $replace): self
    {
        return new self(Str::replaceFirst($search, $replace, $this->value));
    }

    public function replaceLast(string $search, string $replace): self
    {
        return new self(Str::replaceLast($search, $replace, $this->value));
    }

    public function between(string $from, string $to): self
    {
        return new self(Str::between($this->value, $from, $to));
    }

    public function mask(string $character, int $index, ?int $length = null): self
    {
        return new self(Str::mask($this->value, $character, $index, $length));
    }

    public function ascii(): self
    {
        return new self(Str::ascii($this->value));
    }

    public function append(string ...$values): self
    {
        return new self($this->value . implode('', $values));
    }

    public function prepend(string ...$values): self
    {
        return new self(implode('', $values) . $this->value);
    }

    public function substr(int $start, ?int $length = null): self
    {
        return new self(mb_substr($this->value, $start, $length, 'UTF-8'));
    }

    // ── Inspections ────────────────────────────────────────

    /**
     * @param string|array<int, string> $needles
     */
    public function contains(string|array $needles, bool $ignoreCase = false): bool
    {
        return Str::contains($this->value, $needles, $ignoreCase);
    }

    /**
     * @param string|array<int, string> $needles
     */
    public function startsWith(string|array $needles): bool
    {
        return Str::startsWith($this->value, $needles);
    }

    /**
     * @param string|array<int, string> $needles
     */
    public function endsWith(string|array $needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }

    public function length(): int
    {
        return Str::length($this->value);
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function isNotEmpty(): bool
    {
        return $this->value !== '';
    }

    public function isEmail(): bool
    {
        return Str::isEmail($this->value);
    }

    public function isUrl(): bool
    {
        return Str::isUrl($this->value);
    }

    public function isJson(): bool
    {
        return Str::isJson($this->value);
    }

    // ── Conditional ────────────────────────────────────────

    /**
     * Apply a callback if the condition is truthy.
     */
    public function when(mixed $condition, callable $callback, ?callable $default = null): self
    {
        if (Value::filled($condition)) {
            return $callback($this);
        }

        if ($default !== null) {
            return $default($this);
        }

        return $this;
    }

    /**
     * Pipe this Stringable through a callback.
     */
    public function pipe(callable $callback): self
    {
        return new self((string) $callback($this->value));
    }

    /**
     * Apply a callback for side effects, return self unchanged.
     */
    public function tap(callable $callback): self
    {
        $callback($this->value);

        return $this;
    }

    // ── Output ─────────────────────────────────────────────

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
