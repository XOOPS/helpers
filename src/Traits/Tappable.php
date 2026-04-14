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

namespace Xoops\Helpers\Traits;

/**
 * Trait for adding tap() support to any class.
 *
 * Usage:
 *   class MyBuilder {
 *       use Tappable;
 *
 *       public function build(): array { ... }
 *   }
 *
 *   $result = (new MyBuilder())
 *       ->setOption('key', 'value')
 *       ->tap(fn($b) => $logger->info('Building with options'))
 *       ->build();
 */
trait Tappable
{
    /**
     * Call a callback with $this for side effects, then return $this.
     *
     * @return $this
     */
    public function tap(callable $callback): static
    {
        $callback($this);

        return $this;
    }
}
