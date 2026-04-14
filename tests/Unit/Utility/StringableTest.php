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
use Xoops\Helpers\Utility\Stringable;

final class StringableTest extends TestCase
{
    // ── Factory ─────────────────────────────────────────────

    public function testOfCreatesInstance(): void
    {
        $s = Stringable::of('hello');
        self::assertInstanceOf(Stringable::class, $s);
        self::assertSame('hello', $s->toString());
    }

    public function testToStringMagicMethod(): void
    {
        $s = Stringable::of('world');
        self::assertSame('world', (string) $s);
    }

    // ── Immutability ─────────────────────────────────────────

    public function testEachOperationReturnsNewInstance(): void
    {
        $original = Stringable::of('Hello');
        $lower = $original->lower();

        self::assertNotSame($original, $lower);
        self::assertSame('Hello', $original->toString());
        self::assertSame('hello', $lower->toString());
    }

    // ── Transformations ──────────────────────────────────────

    public function testTrimRemovesWhitespace(): void
    {
        self::assertSame('hello', Stringable::of('  hello  ')->trim()->toString());
    }

    public function testLtrimRemovesLeadingWhitespace(): void
    {
        self::assertSame('hello  ', Stringable::of('  hello  ')->ltrim()->toString());
    }

    public function testRtrimRemovesTrailingWhitespace(): void
    {
        self::assertSame('  hello', Stringable::of('  hello  ')->rtrim()->toString());
    }

    public function testLowerConvertsToLowercase(): void
    {
        self::assertSame('hello world', Stringable::of('HELLO WORLD')->lower()->toString());
    }

    public function testUpperConvertsToUppercase(): void
    {
        self::assertSame('HELLO', Stringable::of('hello')->upper()->toString());
    }

    public function testUcfirstCapitalizesFirst(): void
    {
        self::assertSame('Hello world', Stringable::of('hello world')->ucfirst()->toString());
    }

    public function testSlugProducesUrlFriendlyString(): void
    {
        $result = Stringable::of('  Hello World! ')->trim()->slug('-')->toString();
        self::assertSame('hello-world', $result);
    }

    public function testCamelConvertsString(): void
    {
        $result = Stringable::of('hello_world')->camel()->toString();
        self::assertSame('helloWorld', $result);
    }

    public function testSnakeConvertsString(): void
    {
        $result = Stringable::of('helloWorld')->snake()->toString();
        self::assertSame('hello_world', $result);
    }

    public function testStudlyConvertsString(): void
    {
        $result = Stringable::of('hello_world')->studly()->toString();
        self::assertSame('HelloWorld', $result);
    }

    public function testKebabConvertsString(): void
    {
        $result = Stringable::of('helloWorld')->kebab()->toString();
        self::assertSame('hello-world', $result);
    }

    public function testLimitTruncatesWithEllipsis(): void
    {
        $result = Stringable::of('Hello World')->limit(5)->toString();
        self::assertSame('Hello...', $result);
    }

    public function testLimitWithCustomEnd(): void
    {
        $result = Stringable::of('Hello World')->limit(5, '!')->toString();
        self::assertSame('Hello!', $result);
    }

    public function testAppendAddsToEnd(): void
    {
        $result = Stringable::of('hello')->append(' world')->toString();
        self::assertSame('hello world', $result);
    }

    public function testPrependAddsToStart(): void
    {
        $result = Stringable::of('world')->prepend('hello ')->toString();
        self::assertSame('hello world', $result);
    }

    public function testReplaceSubstitutesSubstring(): void
    {
        $result = Stringable::of('foo bar foo')->replace('foo', 'baz')->toString();
        self::assertSame('baz bar baz', $result);
    }

    public function testSubstrExtractsSubstring(): void
    {
        $result = Stringable::of('Hello World')->substr(6)->toString();
        self::assertSame('World', $result);
    }

    // ── Inspections ──────────────────────────────────────────

    public function testContainsReturnsTrueWhenFound(): void
    {
        self::assertTrue(Stringable::of('hello world')->contains('world'));
    }

    public function testContainsReturnsFalseWhenNotFound(): void
    {
        self::assertFalse(Stringable::of('hello world')->contains('xyz'));
    }

    public function testContainsCaseInsensitive(): void
    {
        self::assertTrue(Stringable::of('Hello World')->contains('world', ignoreCase: true));
    }

    public function testStartsWithReturnsTrueForMatch(): void
    {
        self::assertTrue(Stringable::of('hello world')->startsWith('hello'));
    }

    public function testStartsWithReturnsFalseForMismatch(): void
    {
        self::assertFalse(Stringable::of('hello world')->startsWith('world'));
    }

    public function testEndsWithReturnsTrueForMatch(): void
    {
        self::assertTrue(Stringable::of('hello world')->endsWith('world'));
    }

    public function testEndsWithReturnsFalseForMismatch(): void
    {
        self::assertFalse(Stringable::of('hello world')->endsWith('hello'));
    }

    public function testLengthReturnsCharacterCount(): void
    {
        self::assertSame(5, Stringable::of('hello')->length());
    }

    public function testIsEmptyReturnsTrueForEmptyString(): void
    {
        self::assertTrue(Stringable::of('')->isEmpty());
    }

    public function testIsNotEmptyReturnsTrueForNonEmpty(): void
    {
        self::assertTrue(Stringable::of('x')->isNotEmpty());
    }

    public function testIsEmailReturnsTrueForValidEmail(): void
    {
        self::assertTrue(Stringable::of('user@example.com')->isEmail());
    }

    public function testIsEmailReturnsFalseForInvalidEmail(): void
    {
        self::assertFalse(Stringable::of('not-an-email')->isEmail());
    }

    public function testIsUrlReturnsTrueForValidUrl(): void
    {
        self::assertTrue(Stringable::of('https://xoops.org')->isUrl());
    }

    public function testIsUrlReturnsFalseForPlainText(): void
    {
        self::assertFalse(Stringable::of('not a url')->isUrl());
    }

    public function testIsJsonReturnsTrueForValidJson(): void
    {
        self::assertTrue(Stringable::of('{"key":"value"}')->isJson());
    }

    public function testIsJsonReturnsFalseForInvalidJson(): void
    {
        self::assertFalse(Stringable::of('not json')->isJson());
    }

    // ── Conditional ──────────────────────────────────────────

    public function testWhenAppliesCallbackWhenConditionTruthy(): void
    {
        $result = Stringable::of('hello')
            ->when(true, fn(Stringable $s) => $s->upper())
            ->toString();

        self::assertSame('HELLO', $result);
    }

    public function testWhenReturnsSelfWhenConditionFalsy(): void
    {
        $result = Stringable::of('hello')
            ->when(false, fn(Stringable $s) => $s->upper())
            ->toString();

        self::assertSame('hello', $result);
    }

    public function testWhenAppliesDefaultCallbackWhenConditionFalsy(): void
    {
        $result = Stringable::of('hello')
            ->when(
                false,
                fn(Stringable $s) => $s->upper(),
                fn(Stringable $s) => $s->append('_default'),
            )
            ->toString();

        self::assertSame('hello_default', $result);
    }

    // ── Pipe and Tap ─────────────────────────────────────────

    public function testPipePipesValueThroughCallback(): void
    {
        $result = Stringable::of('hello')
            ->pipe(fn(string $v) => strtoupper($v))
            ->toString();

        self::assertSame('HELLO', $result);
    }

    public function testTapExecutesSideEffectAndReturnsSelf(): void
    {
        $seen = '';

        $result = Stringable::of('hello')
            ->tap(function (string $v) use (&$seen): void {
                $seen = $v;
            })
            ->toString();

        self::assertSame('hello', $result);
        self::assertSame('hello', $seen);
    }

    // ── Fluent chain ─────────────────────────────────────────

    public function testFullChainProducesExpectedSlug(): void
    {
        $result = Stringable::of('  Hello World! ')
            ->trim()
            ->slug('-')
            ->toString();

        self::assertSame('hello-world', $result);
    }
}
