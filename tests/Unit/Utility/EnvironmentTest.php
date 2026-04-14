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

namespace Xoops\Helpers\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xoops\Helpers\Utility\Environment;

final class EnvironmentTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::reset();
    }

    // ── Override / detection ────────────────────────────────

    public function testSetOverridesCurrentEnvironment(): void
    {
        Environment::set('staging');
        self::assertSame('staging', Environment::current());
    }

    public function testResetClearsOverride(): void
    {
        Environment::set('development');
        Environment::reset();

        // After reset, should not return the override value
        self::assertNotSame('development', Environment::current());
    }

    public function testIsMatchesCaseInsensitively(): void
    {
        Environment::set('Production');
        self::assertTrue(Environment::is('production'));
        self::assertTrue(Environment::is('PRODUCTION'));
    }

    public function testIsReturnsFalseForMismatch(): void
    {
        Environment::set('production');
        self::assertFalse(Environment::is('development'));
    }

    // ── Named checks ────────────────────────────────────────

    public function testIsProductionReturnsTrueForProduction(): void
    {
        Environment::set('production');
        self::assertTrue(Environment::isProduction());
    }

    public function testIsProductionReturnsFalseForDevelopment(): void
    {
        Environment::set('development');
        self::assertFalse(Environment::isProduction());
    }

    public function testIsDevelopmentMatchesAllAliases(): void
    {
        foreach (['development', 'dev', 'local'] as $alias) {
            Environment::set($alias);
            self::assertTrue(Environment::isDevelopment(), "Expected isDevelopment() to be true for '{$alias}'");
        }
    }

    public function testIsDevelopmentReturnsFalseForProduction(): void
    {
        Environment::set('production');
        self::assertFalse(Environment::isDevelopment());
    }

    public function testIsTestingMatchesAllAliases(): void
    {
        foreach (['testing', 'test'] as $alias) {
            Environment::set($alias);
            self::assertTrue(Environment::isTesting(), "Expected isTesting() to be true for '{$alias}'");
        }
    }

    public function testIsStagingMatchesAllAliases(): void
    {
        foreach (['staging', 'stage'] as $alias) {
            Environment::set($alias);
            self::assertTrue(Environment::isStaging(), "Expected isStaging() to be true for '{$alias}'");
        }
    }

    // ── Environment variable access ─────────────────────────

    public function testGetReturnsValueFromServer(): void
    {
        $_SERVER['XOOPS_TEST_VAR_XYZ'] = 'hello';
        self::assertSame('hello', Environment::get('XOOPS_TEST_VAR_XYZ'));
        unset($_SERVER['XOOPS_TEST_VAR_XYZ']);
    }

    public function testGetReturnsDefaultWhenVariableNotSet(): void
    {
        self::assertSame('fallback', Environment::get('XOOPS_NONEXISTENT_VAR_ZZZ', 'fallback'));
    }

    public function testGetReturnsEmptyStringAsDefaultByDefault(): void
    {
        self::assertSame('', Environment::get('XOOPS_NONEXISTENT_VAR_ZZZ'));
    }

    public function testHasReturnsTrueWhenVariableIsSet(): void
    {
        $_SERVER['XOOPS_HAS_TEST_VAR'] = 'set';
        self::assertTrue(Environment::has('XOOPS_HAS_TEST_VAR'));
        unset($_SERVER['XOOPS_HAS_TEST_VAR']);
    }

    public function testHasReturnsFalseWhenVariableNotSet(): void
    {
        self::assertFalse(Environment::has('XOOPS_DEFINITELY_NOT_SET_ZZZ_QQQ'));
    }

    public function testRequireReturnsValueWhenVariableIsSet(): void
    {
        $_SERVER['XOOPS_REQUIRED_VAR'] = 'required_value';
        self::assertSame('required_value', Environment::require('XOOPS_REQUIRED_VAR'));
        unset($_SERVER['XOOPS_REQUIRED_VAR']);
    }

    public function testRequireThrowsRuntimeExceptionWhenVariableMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('XOOPS_MISSING_REQUIRED_VAR');
        Environment::require('XOOPS_MISSING_REQUIRED_VAR');
    }
}
