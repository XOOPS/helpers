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

if (!class_exists('CriteriaElement')) {
    class CriteriaElement
    {
    }
}

if (!class_exists('XoopsObject')) {
    class XoopsObject
    {
        public function getVar(string $key): mixed
        {
            return null;
        }

        /**
         * @return array<string, mixed>
         */
        public function getValues(): array
        {
            return [];
        }
    }
}

if (!class_exists('XoopsObjectHandler')) {
    class XoopsObjectHandler
    {
        /**
         * @return array<int, XoopsObject>
         */
        public function getObjects(?CriteriaElement $criteria = null): array
        {
            return [];
        }
    }
}
