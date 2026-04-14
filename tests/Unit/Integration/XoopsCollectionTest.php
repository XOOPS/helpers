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

namespace Xoops\Helpers\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Integration\XoopsCollection;

final class XoopsCollectionTest extends TestCase
{
    public function testFromHandlerBuildsCollectionFromObjects(): void
    {
        $handler = new \XoopsObjectHandler();
        $collection = XoopsCollection::fromHandler($handler);

        self::assertInstanceOf(XoopsCollection::class, $collection);
        self::assertCount(0, $collection);
    }

    public function testPluckVarSupportsXoopsStyleObjects(): void
    {
        $item = new class extends \XoopsObject {
            public function __construct(
                private readonly array $values = ['id' => 7, 'title' => 'Hello XOOPS'],
            ) {}

            public function getVar(string $key): mixed
            {
                return $this->values[$key] ?? null;
            }

            public function getValues(): array
            {
                return $this->values;
            }
        };

        $collection = new XoopsCollection([$item]);

        self::assertSame(['Hello XOOPS'], $collection->pluckVar('title')->all());
        self::assertSame([7 => 'Hello XOOPS'], $collection->pluckVar('title', 'id')->all());
    }
}
