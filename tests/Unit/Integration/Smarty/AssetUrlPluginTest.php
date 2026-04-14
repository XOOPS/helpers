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
use Xoops\Helpers\Integration\Smarty\AssetUrlPlugin;
use Xoops\Helpers\Service\Url;

final class AssetUrlPluginTest extends TestCase
{
    /** Minimal object standing in for a Smarty instance. */
    private object $smarty;

    protected function setUp(): void
    {
        $this->smarty = new \stdClass();
        Url::reset();
    }

    protected function tearDown(): void
    {
        Url::reset();
    }

    // ── render() ────────────────────────────────────────────

    public function testRenderReturnStringForGivenPath(): void
    {
        $result = AssetUrlPlugin::render(['path' => 'js/main.js'], $this->smarty);
        self::assertIsString($result);
        self::assertStringContainsString('js/main.js', $result);
    }

    public function testRenderIncludesXoopsBaseUrl(): void
    {
        $result = AssetUrlPlugin::render(['path' => 'css/style.css'], $this->smarty);
        // XOOPS_URL is defined as 'http://localhost' in bootstrap
        self::assertStringContainsString('http://localhost', $result);
    }

    public function testRenderWithMissingPathUsesEmptyString(): void
    {
        $result = AssetUrlPlugin::render([], $this->smarty);
        self::assertIsString($result);
    }

    public function testRenderWithBooleanSecureTrue(): void
    {
        $result = AssetUrlPlugin::render(['path' => 'img/logo.png', 'secure' => true], $this->smarty);
        self::assertStringContainsString('https://', $result);
    }

    public function testRenderWithStringSecureTrue(): void
    {
        // Smarty template params arrive as strings
        $result = AssetUrlPlugin::render(['path' => 'img/logo.png', 'secure' => 'true'], $this->smarty);
        self::assertStringContainsString('https://', $result);
    }

    public function testRenderWithStringSecureFalseProducesHttp(): void
    {
        $result = AssetUrlPlugin::render(['path' => 'img/logo.png', 'secure' => 'false'], $this->smarty);
        self::assertStringNotContainsString('https://', $result);
    }

    public function testRenderWithSecureDefaultProducesHttp(): void
    {
        $result = AssetUrlPlugin::render(['path' => 'img/logo.png'], $this->smarty);
        self::assertStringNotContainsString('https://', $result);
    }

    public function testRenderWithUnrecognisedSecureValueDefaultsToFalse(): void
    {
        // filter_var('yes', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null
        // The null coalescing fallback (?? false) should suppress it silently → HTTP
        $result = AssetUrlPlugin::render(['path' => 'img/logo.png', 'secure' => 'yes'], $this->smarty);
        self::assertStringNotContainsString('https://', $result);
    }

    public function testRenderWithNumericOneSecureProducesHttps(): void
    {
        // filter_var('1', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true
        // '1' is a recognised truthy boolean string → HTTPS
        $result = AssetUrlPlugin::render(['path' => 'img/logo.png', 'secure' => '1'], $this->smarty);
        self::assertStringContainsString('https://', $result);
    }

    // ── register() ──────────────────────────────────────────

    public function testRegisterCallsRegisterPluginWhenMethodExists(): void
    {
        $registered = [];

        $smarty = new class($registered) {
            public function __construct(private array &$registered) {}

            public function registerPlugin(string $type, string $name, mixed $callback): void
            {
                $this->registered[] = ['type' => $type, 'name' => $name, 'callback' => $callback];
            }
        };

        AssetUrlPlugin::register($smarty);

        self::assertCount(1, $registered);
        self::assertSame('function', $registered[0]['type']);
        self::assertSame('asset_url', $registered[0]['name']);
        self::assertSame([AssetUrlPlugin::class, 'render'], $registered[0]['callback']);
    }

    public function testRegisterDoesNotThrowWhenRegisterPluginMissing(): void
    {
        // Object without registerPlugin — should be silently ignored
        AssetUrlPlugin::register(new \stdClass());
        self::assertTrue(true); // reached without exception
    }
}
