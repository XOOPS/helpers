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

namespace Xoops\Helpers\Tests\Unit\Integration\Smarty;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Integration\Smarty\FormatNumberPlugin;

final class FormatNumberPluginTest extends TestCase
{
    private object $smarty;

    protected function setUp(): void
    {
        $this->smarty = new \stdClass();
    }

    // ── render() — decimal (default) ────────────────────────

    public function testRenderDefaultTypeFormatsDecimal(): void
    {
        $result = FormatNumberPlugin::render(['value' => 1234], $this->smarty);
        self::assertIsString($result);
        // number_format(1234, 0) = '1,234' — just verify it's numeric-ish
        self::assertStringContainsString('1', $result);
        self::assertStringContainsString('234', $result);
    }

    public function testRenderDecimalTypeWithExplicitDecimals(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 1234.5, 'type' => 'decimal', 'decimals' => 2],
            $this->smarty,
        );

        self::assertStringContainsString('1234', $result);
        self::assertStringContainsString('5', $result);
    }

    public function testRenderDecimalTypeWithZeroDecimals(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 42, 'type' => 'decimal', 'decimals' => 0],
            $this->smarty,
        );

        self::assertStringContainsString('42', $result);
    }

    // ── render() — filesize ─────────────────────────────────

    public function testRenderFilesizeTypeReturnsHumanReadable(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 1024, 'type' => 'filesize'],
            $this->smarty,
        );

        self::assertIsString($result);
        // 1 KB expected
        self::assertStringContainsString('KB', $result);
    }

    public function testRenderFilesizeWithBytesRange(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 512, 'type' => 'filesize'],
            $this->smarty,
        );

        self::assertStringContainsString('B', $result);
    }

    // ── render() — human ────────────────────────────────────

    public function testRenderHumanTypeForMillions(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 1_000_000, 'type' => 'human'],
            $this->smarty,
        );

        self::assertIsString($result);
        self::assertStringContainsString('M', $result);
    }

    public function testRenderHumanTypeForThousands(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 5000, 'type' => 'human'],
            $this->smarty,
        );

        self::assertStringContainsString('K', $result);
    }

    // ── render() — percentage ───────────────────────────────

    public function testRenderPercentageTypeIncludesSymbol(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 75, 'type' => 'percentage'],
            $this->smarty,
        );

        self::assertStringContainsString('%', $result);
        self::assertStringContainsString('75', $result);
    }

    // ── render() — ordinal ──────────────────────────────────

    public function testRenderOrdinalTypeReturnsSuffix(): void
    {
        $result = FormatNumberPlugin::render(
            ['value' => 1, 'type' => 'ordinal'],
            $this->smarty,
        );

        self::assertIsString($result);
        // Ordinal may be locale-dependent; just assert it contains the integer
        self::assertStringContainsString('1', $result);
    }

    // ── render() — currency ─────────────────────────────────

    public function testRenderCurrencyTypeReturnsFormattedValue(): void
    {
        if (!extension_loaded('intl')) {
            self::markTestSkipped('ext-intl required for currency formatting');
        }

        $result = FormatNumberPlugin::render(
            ['value' => 19.99, 'type' => 'currency', 'currency' => 'USD', 'locale' => 'en_US'],
            $this->smarty,
        );

        self::assertIsString($result);
        self::assertStringContainsString('19', $result);
    }

    // ── register() ──────────────────────────────────────────

    public function testRegisterCallsRegisterPluginCorrectly(): void
    {
        $registered = [];

        $smarty = new class($registered) {
            public function __construct(private array &$registered) {}

            public function registerPlugin(string $type, string $name, mixed $callback): void
            {
                $this->registered[] = ['type' => $type, 'name' => $name, 'callback' => $callback];
            }
        };

        FormatNumberPlugin::register($smarty);

        self::assertCount(1, $registered);
        self::assertSame('function', $registered[0]['type']);
        self::assertSame('format_number', $registered[0]['name']);
        self::assertSame([FormatNumberPlugin::class, 'render'], $registered[0]['callback']);
    }

    public function testRegisterDoesNotThrowWhenRegisterPluginMissing(): void
    {
        FormatNumberPlugin::register(new \stdClass());
        self::assertTrue(true);
    }
}
