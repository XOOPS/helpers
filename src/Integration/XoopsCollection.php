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

namespace Xoops\Helpers\Integration;

use Xoops\Helpers\Utility\Collection;

/**
 * XOOPS-aware collection extending the base Collection.
 *
 * Adds factory methods and overrides for working with
 * XoopsObject instances and XoopsObjectHandler results.
 *
 * Usage:
 *   $articles = XoopsCollection::fromHandler($articleHandler, $criteria);
 *   $titles = $articles->pluck('title');
 *   $byAuthor = $articles->groupBy('author_id');
 *
 * @extends Collection<array-key, mixed>
 */
final class XoopsCollection extends Collection
{
    /**
     * Create a collection from a XOOPS object handler.
     *
     * @param \XoopsObjectHandler       $handler  The object handler
     * @param \CriteriaElement|null     $criteria Optional filter criteria
     * @return self
     */
    public static function fromHandler(object $handler, ?object $criteria = null): self
    {
        $objects = [];

        if (method_exists($handler, 'getObjects')) {
            $objects = $handler->getObjects($criteria) ?: [];
        }

        return new self($objects);
    }

    /**
     * Extract values from XOOPS objects using their getVar() method.
     *
     * Overrides the base pluck() to support XoopsObject's getVar() API.
     *
     * @return self
     */
    public function pluckVar(string $valueKey, ?string $keyKey = null): self
    {
        $results = [];

        foreach ($this->all() as $item) {
            $value = self::getObjectVar($item, $valueKey);

            if ($keyKey === null) {
                $results[] = $value;
            } else {
                $resolvedKey = self::getObjectVar($item, $keyKey);

                if (is_int($resolvedKey) || is_string($resolvedKey)) {
                    $results[$resolvedKey] = $value;
                } else {
                    $results[] = $value;
                }
            }
        }

        return new self($results);
    }

    /**
     * Convert all XOOPS objects to arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static function (mixed $item): array {
            if (is_object($item) && method_exists($item, 'getValues')) {
                return $item->getValues();
            }

            if (is_object($item) && method_exists($item, 'toArray')) {
                return $item->toArray();
            }

            if (is_array($item)) {
                return $item;
            }

            return (array) $item;
        }, $this->all());
    }

    /**
     * Get a variable from a XOOPS object or generic object/array.
     */
    private static function getObjectVar(mixed $item, string $key): mixed
    {
        // XoopsObject::getVar()
        if (is_object($item) && method_exists($item, 'getVar')) {
            return $item->getVar($key);
        }

        // Generic object — use get_object_vars() to avoid private/protected access errors
        if (is_object($item)) {
            $publicProps = get_object_vars($item);

            return $publicProps[$key] ?? null;
        }

        // Array access
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        return null;
    }
}
