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
use Xoops\Helpers\Integration\Smarty\CssClassesPlugin;

final class CssClassesPluginTest extends TestCase
{
    private object $smarty;

    protected function setUp(): void
    {
        $this->smarty = new \stdClass();
    }

    // ── render() ────────────────────────────────────────────

    public function testRenderWithUnconditionalClasses(): void
    {
        $result = CssClassesPlugin::render(
            ['classes' => ['btn', 'btn-lg']],
            $this->smarty,
        );

        self::assertSame('btn btn-lg', $result);
    }

    public function testRenderWithConditionalClassTrue(): void
    {
        $result = CssClassesPlugin::render(
            ['classes' => ['btn', 'btn-primary' => true, 'disabled' => false]],
            $this->smarty,
        );

        self::assertSame('btn btn-primary', $result);
    }

    public function testRenderWithAllConditionalClassesFalse(): void
    {
        $result = CssClassesPlugin::render(
            ['classes' => ['active' => false, 'hidden' => false]],
            $this->smarty,
        );

        self::assertSame('', $result);
    }

    public function testRenderWithMixedConditionals(): void
    {
        $result = CssClassesPlugin::render(
            ['classes' => [
                'nav',
                'nav-item',
                'active' => true,
                'disabled' => false,
            ]],
            $this->smarty,
        );

        self::assertSame('nav nav-item active', $result);
    }

    public function testRenderWithScalarFallbackEscapesHtml(): void
    {
        // When classes is not an array, it is escaped as a plain string
        $result = CssClassesPlugin::render(
            ['classes' => '<script>alert(1)</script>'],
            $this->smarty,
        );

        self::assertStringNotContainsString('<script>', $result);
        self::assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testRenderWithScalarPlainString(): void
    {
        $result = CssClassesPlugin::render(
            ['classes' => 'btn btn-primary'],
            $this->smarty,
        );

        self::assertSame('btn btn-primary', $result);
    }

    public function testRenderWithMissingClassesParameterReturnsEmpty(): void
    {
        $result = CssClassesPlugin::render([], $this->smarty);
        self::assertSame('', $result);
    }

    public function testRenderWithEmptyArrayReturnsEmpty(): void
    {
        $result = CssClassesPlugin::render(['classes' => []], $this->smarty);
        self::assertSame('', $result);
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

        CssClassesPlugin::register($smarty);

        self::assertCount(1, $registered);
        self::assertSame('function', $registered[0]['type']);
        self::assertSame('css_classes', $registered[0]['name']);
        self::assertSame([CssClassesPlugin::class, 'render'], $registered[0]['callback']);
    }

    public function testRegisterDoesNotThrowWhenRegisterPluginMissing(): void
    {
        CssClassesPlugin::register(new \stdClass());
        self::assertTrue(true);
    }
}
