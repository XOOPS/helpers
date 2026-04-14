# XOOPS Helpers

Convention-over-configuration utility and service helpers for XOOPS CMS development.

[![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)

**41 source files. 151 tests. Zero configuration. XSS-safe HTML by default. One `composer require`.**

## What Is This?

XOOPS Helpers is a standalone utility library that makes every XOOPS module **safer and shorter** by replacing the repetitive, error-prone boilerplate every module developer writes over and over:

```php
// Before — scattered across every XOOPS module
$escaped  = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$url      = XOOPS_URL . '/modules/' . $dirname . '/article.php?id=' . $id;
$path     = XOOPS_ROOT_PATH . '/modules/' . $dirname . '/language/' . $language . '/blocks.php';
$sitename = $GLOBALS['xoopsConfig']['sitename'];

// After
$escaped  = HtmlBuilder::escape($value);
$url      = Url::module($dirname, 'article.php', ['id' => $id]);
$path     = Path::module($dirname, "language/{$language}/blocks.php");
$sitename = Config::get('system.sitename');
```

> **That first line is not just shorter — it is structurally safer.**
> Every manual `htmlspecialchars()` call is a place where a future developer can introduce
> a stored XSS vulnerability by forgetting it once. `HtmlBuilder` escapes all **attribute
> values and class names** automatically — the source of the vast majority of real-world XSS.
> Tag content is your responsibility, intentionally: content can legitimately contain HTML
> (rendered markup, trusted template fragments). The safe path is explicit: pass user-supplied
> content through `HtmlBuilder::escape()`. This `htmlspecialchars` pattern appears **30+ times**
> in the XOOPS Core alone — each one a place where this library makes the correct choice the
> easiest choice.

## Requirements

- PHP 8.2 or later with the `ext-mbstring` extension
- No other runtime dependencies

Optional extensions for enhanced functionality:
- `ext-intl` — locale-aware number and date formatting
- `ext-apcu` — APCu caching backend
- `ext-zip` — zip/unzip filesystem operations

## Installation

```bash
composer require xoops/helpers
```

## Quick Start

```php
use Xoops\Helpers\Utility\HtmlBuilder;
use Xoops\Helpers\Service\Url;
use Xoops\Helpers\Service\Path;
use Xoops\Helpers\Service\Config;
use Xoops\Helpers\Service\Cache;
use Xoops\Helpers\Utility\Arr;
use Xoops\Helpers\Utility\Str;
use Xoops\Helpers\Utility\Number;
use Xoops\Helpers\Utility\Collection;

// HTML — attribute values escaped automatically; use text() for tag content
HtmlBuilder::attributes(['class' => 'btn', 'disabled' => true, 'data-id' => $userInput]);
HtmlBuilder::classes(['btn', 'btn-primary' => $isPrimary, 'disabled' => false]);
HtmlBuilder::tag('div', ['class' => 'alert'], HtmlBuilder::text($userMessage)); // user text
HtmlBuilder::tag('div', ['class' => 'body'], $renderedHtmlBlock);               // trusted HTML

// URLs — zero concatenation
Url::module('news', 'article.php', ['id' => 42]);
Url::asset('themes/starter/css/style.css');
Url::theme('starter', 'images/logo.png');

// Paths — cross-platform, always correct; languageFile() resolves language fallback
Path::module('news', 'language/english/main.php');
Path::languageFile('news', $language, 'main.php'); // tries $language, falls back to english
Path::storage('caches/xmf');
Path::uploads('images/avatars');

// Config — dot notation, auto-cached
Config::get('system.sitename', 'XOOPS');
Config::get('news.items_per_page', 10);

// Cache — compute-and-cache in one call
$articles = Cache::remember('news_latest', 3600, fn() => loadArticles());

// Arrays — dot notation, pluck, group, filter
$value   = Arr::get($config, 'database.host', 'localhost');
$names   = Arr::pluck($users, 'uname', 'uid');
$grouped = Arr::groupBy($articles, 'category_id');

// Strings — slug, validation, case conversion
Str::slug('Hello World');        // "hello-world"
Str::isEmail('a@example.com');   // true
Str::camel('module_config');     // "moduleConfig"
Str::limit($body, 150);          // "First 150 chars..."
Str::random(32);                 // cryptographically secure

// Numbers — human-readable formatting
Number::fileSize(1572864);       // "1.50 MB"
Number::forHumans(2300000);      // "2.3M"
Number::ordinal(21);             // "21st"
Number::currency(99.99, 'EUR', 'de_DE');

// Collections — fluent data transformation
Collection::make($items)
    ->filter(fn($item) => $item['active'])
    ->sortBy('name')
    ->pluck('title', 'id')
    ->all();
```

## Library Contents

### Tier 0 — Utility (Pure PHP, zero XOOPS dependency)

These work anywhere — CLI scripts, cron jobs, unit tests — no XOOPS boot required.

| Class | Purpose |
|-------|---------|
| [`HtmlBuilder`](src/Utility/HtmlBuilder.php) | **XSS-safe HTML:** `attributes`, `classes`, `tag`, `escape`, `text`, `stylesheet`, `script`, `meta` — attribute values escaped automatically; use `text()` to explicitly escape tag content |
| [`Arr`](src/Utility/Arr.php) | Array helpers with dot notation: `get`, `set`, `has`, `pluck`, `groupBy`, `sortBy`, `where`, `flatten`, `dot`/`undot`, `only`/`except`, `first`/`last`, `wrap`, `collapse` |
| [`Str`](src/Utility/Str.php) | String helpers: `slug`, `camel`/`snake`/`studly`/`kebab`, `limit`, `random`, `contains`/`startsWith`/`endsWith`, `between`, `mask`, `isEmail`/`isUrl`/`isIp`/`isJson`/`isHexColor` |
| [`Number`](src/Utility/Number.php) | Number formatting: `format`, `fileSize`, `forHumans`, `percentage`, `ordinal`, `currency`, `clamp` |
| [`Date`](src/Utility/Date.php) | Date helpers with injectable time source: `now`, `range`, `diff`, `isValid`, `addDays`/`subDays`, `isWeekend`/`isToday`/`isPast`/`isFuture`, `reformat`, `age` |
| [`Value`](src/Utility/Value.php) | Value resolution: `value` (Closure resolver), `blank`/`filled`, `optional` (null-safe access), `once` (memoization), `missing` (sentinel) |
| [`Collection`](src/Utility/Collection.php) | Fluent array wrapper: `map`, `filter`, `reject`, `reduce`, `pluck`, `groupBy`, `sortBy`, `first`/`last`, `chunk`, `take`/`skip`, `sum`/`avg`/`min`/`max`, `when`, `pipe`, `tap` |
| [`Pipeline`](src/Utility/Pipeline.php) | Data transformation chains: `Pipeline::send($v)->pipe(fn)->pipe(fn)->thenReturn()` |
| [`Stringable`](src/Utility/Stringable.php) | Fluent string builder: `Stringable::of($s)->trim()->lower()->slug()->toString()` |
| [`Filesystem`](src/Utility/Filesystem.php) | File operations: `readJson`/`putJson`, `mimeType`, `isImage`, `mkdir`, `deleteDirectory`, `copyDirectory`, `zip`/`unzip`, `readChunked` |
| [`Environment`](src/Utility/Environment.php) | Runtime detection: `isProduction`/`isDevelopment`/`isTesting`, `get`/`require`/`has` |
| [`Benchmark`](src/Utility/Benchmark.php) | Profiling: `measure` (time + memory), `time`, `average` (multi-iteration) |
| [`Encoding`](src/Utility/Encoding.php) | URL-safe base64: `base64UrlEncode`/`base64UrlDecode` |
| [`Data`](src/Utility/Data.php) | Conversion: `toArray`, `toObject`, `toQueryString`, `fromQueryString` |
| [`Retry`](src/Utility/Retry.php) | Error recovery: `retry` (with backoff), `rescue` (with fallback) |
| [`ThrowHelper`](src/Utility/ThrowHelper.php) | Guard clauses: `throwIf`, `throwUnless` |
| [`Transform`](src/Utility/Transform.php) | Conditional transforms: `transform` (if filled), `when` (predicate-based) |
| [`Tap`](src/Utility/Tap.php) | Side-effect helper: call callback, return original value |

### Tier 1 — Contracts (Interfaces)

| Interface | Purpose |
|-----------|---------|
| [`PathLocatorInterface`](src/Contracts/PathLocatorInterface.php) | Filesystem path resolution |
| [`UrlGeneratorInterface`](src/Contracts/UrlGeneratorInterface.php) | URL generation |
| [`CacheInterface`](src/Contracts/CacheInterface.php) | Cache operations |
| [`ConfigProviderInterface`](src/Contracts/ConfigProviderInterface.php) | Configuration loading |
| [`DateTimeProviderInterface`](src/Contracts/DateTimeProviderInterface.php) | Clock abstraction for testing |

### Tier 2 — Service Facades (Zero-config, XOOPS-aware)

| Facade | Purpose | Override |
|--------|---------|----------|
| [`Path`](src/Service/Path.php) | `Path::base()`, `module()`, `storage()`, `uploads()`, `themes()`, `languageFile()` | `Path::use($locator)` |
| [`Url`](src/Service/Url.php) | `Url::to()`, `asset()`, `module()`, `theme()` | `Url::use($generator)` |
| [`Config`](src/Service/Config.php) | `Config::get()`, `set()`, `has()`, `all()`, `registerLoader()` | `Config::setProvider($p)` |
| [`Cache`](src/Service/Cache.php) | `Cache::get()`, `set()`, `forget()`, `remember()`, `flush()` | `Cache::use($adapter)` |

All facades work immediately using XOOPS constants (`XOOPS_ROOT_PATH`, `XOOPS_URL`, etc.). Override with `::use()` for testing or custom installations. Reset with `::reset()`.

### Tier 3 — Providers (Default implementations)

| Provider | Purpose |
|----------|---------|
| [`DefaultPathLocator`](src/Provider/DefaultPathLocator.php) | Maps to XOOPS constants |
| [`DefaultUrlGenerator`](src/Provider/DefaultUrlGenerator.php) | Uses `XOOPS_URL`, falls back to `$_SERVER` |
| [`XoopsCacheAdapter`](src/Provider/XoopsCacheAdapter.php) | Auto-detects: XoopsCache, APCu, or file cache |
| [`ArrayCache`](src/Provider/ArrayCache.php) | In-memory cache for testing |
| [`SystemDateTimeProvider`](src/Provider/SystemDateTimeProvider.php) | System clock |

### Tier 4 — Integration (XOOPS-specific)

| Component | Purpose |
|-----------|---------|
| [`XoopsCollection`](src/Integration/XoopsCollection.php) | `XoopsCollection::fromHandler($handler, $criteria)` with `pluckVar()` for `getVar()` |
| [`AssetUrlPlugin`](src/Integration/Smarty/AssetUrlPlugin.php) | Smarty: `<{asset_url path="css/style.css"}>` |
| [`FormatNumberPlugin`](src/Integration/Smarty/FormatNumberPlugin.php) | Smarty: `<{format_number value=$size type="filesize"}>` |
| [`CssClassesPlugin`](src/Integration/Smarty/CssClassesPlugin.php) | Smarty: `<{css_classes classes=$classArray}>` |
| [`PluginRegistrar`](src/Integration/Smarty/PluginRegistrar.php) | Register all Smarty plugins at once |

### Cross-cutting

| Component | Purpose |
|-----------|---------|
| [`Tappable`](src/Traits/Tappable.php) | Trait adding `tap()` to any class |
| [`functions.php`](src/functions.php) | Optional global function wrappers (not auto-loaded) |

## Architecture

Dependencies flow downward only. Tier 0 classes can be used in any PHP 8.2+ project without XOOPS — in CLI scripts, cron jobs, and unit tests with no bootstrap required.

```mermaid
graph TD
    T4["**Tier 4 · Integration**
    Depend on XOOPS classes
    XoopsObject · Smarty"]

    T3["**Tier 3 · Provider**
    Default implementations
    XOOPS-aware"]

    T2["**Tier 2 · Service**
    Static facades
    Depend on XOOPS constants"]

    T1["**Tier 1 · Contracts**
    Interfaces only
    No implementation"]

    T0["**Tier 0 · Utility**
    Pure PHP · Zero dependencies
    Works anywhere"]

    T4 --> T3
    T3 --> T2
    T2 --> T1
    T1 --> T0

    style T4 fill:#b7e0ff,stroke:#4a90d9,color:#000
    style T3 fill:#c8f0d0,stroke:#3a9a5c,color:#000
    style T2 fill:#fff3b0,stroke:#c8a200,color:#000
    style T1 fill:#ffd6a5,stroke:#d48000,color:#000
    style T0 fill:#ffadad,stroke:#c0392b,color:#000
```

## Optional Global Functions

The file `src/functions.php` provides short function wrappers like `collect()`, `str()`, `pipeline()`, `tap()`, `retry()`, `env()`, etc. It is **not auto-loaded** — opt in explicitly.

**Recommended pattern:** load it once in your XOOPS bootstrap, not in individual module files. This prevents redundant `require` calls across a multi-module installation:

```php
// In mainfile.php or a central preload script — once per request
if (file_exists(XOOPS_ROOT_PATH . '/vendor/xoops/helpers/src/functions.php')) {
    require_once XOOPS_ROOT_PATH . '/vendor/xoops/helpers/src/functions.php';
}
```

If you are building a single module and do not control the bootstrap, load it in your module's entry point:

```php
require_once 'vendor/xoops/helpers/src/functions.php';

$slug = str('Hello World')->slug()->toString();
$data = collect($items)->filter(fn($i) => $i['active'])->pluck('name')->all();
$value = retry(3, fn() => fetchFromApi(), sleepMs: 500);
```

All functions are guarded with `function_exists()` to prevent fatal redeclaration errors.

## Compatibility

### XOOPS 2.5.x

Fully compatible. Designed for inclusion in XOOPS 2.5.12+.

### XMF 1.x (`xoops/xmf`)

No conflicts. Different namespace (`Xoops\Helpers\` vs `Xmf\`), no shared class names, no shared global functions. Both can be loaded simultaneously via Composer.

Where both libraries offer related functionality, they serve different scopes:

| Area | XMF 1.x | XOOPS Helpers |
|------|---------|---------------|
| URL/Path | `$helper->url()` — module-scoped | `Url::module()` — global, works without module context |
| Config | `$helper->getConfig()` — per-module handler | `Config::get('mod.key')` — dot notation, cached |
| Cache | `Helper\Cache::cacheRead()` — module-prefixed | `Cache::remember()` — global, auto-backend |
| Random | `Random::generateKey()` — SHA512 hash tokens | `Str::random()` — URL-safe strings, configurable length |
| SEO | `Metagen::generateSeoTitle()` — full meta tags | `Str::slug()` — pure string transformation |

### Migration Strategy

You do not need to refactor existing XMF 1.x code to adopt this library. Both coexist safely. The recommended approach depends on where you are in a project:

**Starting a new module** — use XOOPS Helpers exclusively from the first line. There is no legacy to consider and you get the full benefit of automatic escaping, dot-notation config, and fluent collections from day one.

**Actively developing an existing module** — use XOOPS Helpers for all new code and any functions you touch during the current sprint. When you open a file to add a feature, convert the XMF 1.x patterns in that file as you go. Do not schedule a dedicated refactoring sprint; let the migration happen organically as the module evolves.

**Maintaining a stable module with no active development** — do nothing. The libraries coexist with zero conflicts. The migration cost is not justified by a pure maintenance ticket. If it is not broken, leave it until you have a reason to open the file.

When you do migrate a specific pattern, the Cache facade is the most common conversion:

```php
// XMF 1.x — before
if (!$data = \XoopsCache::read("{$dirname}_config")) {
    $data = xoops_getModuleConfig($dirname);
    \XoopsCache::write("{$dirname}_config", $data);
}

// XOOPS Helpers — after
$data = Cache::remember("{$dirname}_config", 3600, fn() => xoops_getModuleConfig($dirname));
```

### XMF 2.0 (`xoops/xmf` next generation)

Designed as a companion. XMF 2.0 provides the architectural framework (Repository, EventBus, Container, QueryBuilder); XOOPS Helpers provides the day-to-day utilities (Arr, Str, Number, HtmlBuilder, Collection). XMF 2.0 will declare `xoops/helpers` as a dependency — requiring XMF 2.0 pulls this library in automatically.

## Testing

```bash
composer install
vendor/bin/phpunit
```

All services are mockable for testing:

```php
use Xoops\Helpers\Service\{Path, Url, Config, Cache};
use Xoops\Helpers\Provider\ArrayCache;

// Inject test implementations
Cache::use(new ArrayCache());
Config::registerLoader('mymod', fn() => ['key' => 'value']);

// Reset after tests
Cache::reset();
Config::reset();
Path::reset();
Url::reset();
```

The `Date` utility accepts an injectable time provider:

```php
use Xoops\Helpers\Utility\Date;
use Xoops\Helpers\Contracts\DateTimeProviderInterface;

Date::setProvider(new class implements DateTimeProviderInterface {
    public function now(): \DateTimeImmutable {
        return new \DateTimeImmutable('2025-06-15 12:00:00');
    }
});

Date::isToday('2025-06-15'); // true — deterministic in tests
Date::resetProvider();
```

## Contributing

Contributions are welcome. Please follow XOOPS coding standards:
- `declare(strict_types=1)` in every file
- PHP 8.2+ features (readonly, match, named arguments, union types)
- Final classes for utility classes
- Full type hints on all methods
- PHPUnit tests for all new functionality

## Documentation

See [TUTORIAL.md](TUTORIAL.md) for a comprehensive guide with before/after comparisons from real XOOPS Core and module code.

## License

[GNU GPL v2](LICENSE) or later. See [LICENSE](LICENSE) for details.
