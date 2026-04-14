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
use Xoops\Helpers\Utility\HtmlBuilder;

final class HtmlBuilderTest extends TestCase
{
    public function testAttributesRendersSimple(): void
    {
        $result = HtmlBuilder::attributes(['class' => 'btn', 'id' => 'submit']);
        self::assertSame('class="btn" id="submit"', $result);
    }

    public function testAttributesBooleanTrue(): void
    {
        $result = HtmlBuilder::attributes(['disabled' => true]);
        self::assertSame('disabled', $result);
    }

    public function testAttributesBooleanFalseOmitted(): void
    {
        $result = HtmlBuilder::attributes(['hidden' => false, 'class' => 'visible']);
        self::assertSame('class="visible"', $result);
    }

    public function testAttributesNullOmitted(): void
    {
        $result = HtmlBuilder::attributes(['title' => null, 'class' => 'btn']);
        self::assertSame('class="btn"', $result);
    }

    public function testAttributesEscapesXss(): void
    {
        $result = HtmlBuilder::attributes(['data-value' => '"><script>alert(1)</script>']);
        self::assertStringContainsString('&quot;&gt;&lt;script&gt;', $result);
    }

    public function testClassesWithUnconditional(): void
    {
        $result = HtmlBuilder::classes(['btn', 'btn-lg']);
        self::assertSame('btn btn-lg', $result);
    }

    public function testClassesWithConditional(): void
    {
        $result = HtmlBuilder::classes([
            'btn',
            'btn-primary' => true,
            'btn-disabled' => false,
        ]);
        self::assertSame('btn btn-primary', $result);
    }

    public function testTagSelfClosing(): void
    {
        $result = HtmlBuilder::tag('input', ['type' => 'text', 'name' => 'q'], selfClose: true);
        self::assertSame('<input type="text" name="q" />', $result);
    }

    public function testTagWithContent(): void
    {
        $result = HtmlBuilder::tag('span', ['class' => 'label'], 'Hello');
        self::assertSame('<span class="label">Hello</span>', $result);
    }

    public function testEscape(): void
    {
        self::assertSame('&lt;script&gt;', HtmlBuilder::escape('<script>'));
        self::assertSame('a&amp;b', HtmlBuilder::escape('a&b'));
    }

    public function testStylesheet(): void
    {
        $result = HtmlBuilder::stylesheet('/css/style.css');
        self::assertSame('<link rel="stylesheet" href="/css/style.css" />', $result);
    }

    public function testScript(): void
    {
        $result = HtmlBuilder::script('/js/app.js');
        self::assertSame('<script src="/js/app.js"></script>', $result);
    }

    // ── text() ──────────────────────────────────────────────

    public function testTextEscapesHtmlEntities(): void
    {
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', HtmlBuilder::text('<script>alert(1)</script>'));
    }

    public function testTextProducesSameResultAsEscape(): void
    {
        $input = '"Hello" & <World>';
        self::assertSame(HtmlBuilder::escape($input), HtmlBuilder::text($input));
    }

    public function testTextEscapesAmpersand(): void
    {
        self::assertSame('a&amp;b', HtmlBuilder::text('a&b'));
    }

    public function testTextEscapesDoubleQuotes(): void
    {
        self::assertSame('say &quot;hi&quot;', HtmlBuilder::text('say "hi"'));
    }

    public function testTextLeavesPlainStringUnchanged(): void
    {
        self::assertSame('Hello World', HtmlBuilder::text('Hello World'));
    }

    public function testTextEscapesXssPayload(): void
    {
        $xss = '"><img src=x onerror=alert(1)>';
        $result = HtmlBuilder::text($xss);
        self::assertStringNotContainsString('<img', $result);
        self::assertStringContainsString('&lt;img', $result);
    }

    public function testRecommendedSafePatternTagWithText(): void
    {
        // The documented safe pattern: HtmlBuilder::tag($tag, $attrs, HtmlBuilder::text($userInput))
        $payload = '<script>alert("xss")</script>';
        $result  = HtmlBuilder::tag('div', ['class' => 'alert'], HtmlBuilder::text($payload));

        // The outer tag and its attribute must be present
        self::assertStringStartsWith('<div', $result);
        self::assertStringContainsString('class="alert"', $result);
        // The user payload must be escaped inside the tag body
        self::assertStringNotContainsString('<script>', $result);
        self::assertStringContainsString('&lt;script&gt;', $result);
        // Full expected output
        self::assertSame(
            '<div class="alert">&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</div>',
            $result,
        );
    }
}
