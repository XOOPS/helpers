# XOOPS Helpers: The Developer's Toolkit

## Stop Writing Boilerplate. Start Building Features.

Every XOOPS module developer knows the drill. You need a URL to a module page:

```php
// kernel/notification.php:741
$tags['X_MODULE_URL'] = XOOPS_URL . '/modules/' . $module->getVar('dirname') . '/';
```

You need a file path with a language fallback:

```php
// kernel/block.php:602-608
if (file_exists(XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php')) {
    include_once XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php';
} elseif (file_exists(XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/english/blocks.php')) {
    include_once XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/english/blocks.php';
}
```

You need to safely output a user-provided value in an HTML attribute:

```php
// banners.php:204
htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
htmlspecialchars($imageurl, ENT_QUOTES | ENT_HTML5, 'UTF-8')
htmlspecialchars($clickurl, ENT_QUOTES | ENT_HTML5, 'UTF-8')
```

And you do it again. And again. Across every module, every admin page, every block.

**XOOPS Helpers eliminates all of that.** One library. Zero configuration. Convention over configuration.

```php
use Xoops\Helpers\Service\Url;
use Xoops\Helpers\Service\Path;
use Xoops\Helpers\Utility\HtmlBuilder;

$url  = Url::module($module->getVar('dirname'));
$path = Path::module($dirname, 'language/' . $language . '/blocks.php');
$html = HtmlBuilder::attributes(['data-name' => $sitename, 'href' => $clickurl]);
```

---

## Security by Design

Those three `htmlspecialchars()` calls above are not just boilerplate — each one is a gap where a future developer can introduce a stored XSS vulnerability by forgetting it once. One missed call on a user profile link is enough to compromise an entire admin panel. The `htmlspecialchars` pattern appears **30+ times** in the XOOPS Core alone. That is 30+ places where correctness depends entirely on developer discipline rather than on the architecture.

XOOPS Helpers closes those gaps structurally. `HtmlBuilder` escapes all attribute values automatically. You cannot forget it because there is nothing to forget — escaping is what the method does, not an option you have to remember to pass.

**The contract:** `HtmlBuilder` escapes all **attribute values and class names** automatically. Tag **content** is your responsibility — intentionally, because content can legitimately contain HTML (rendered markup, template fragments, trusted output from a WYSIWYG editor). The library does not guess your intent.

**Old way — unsafe attribute output, nothing to stop you forgetting:**

```php
// One forgotten htmlspecialchars call on an attribute = stored XSS
$button = "<button data-name='" . $userInput . "' data-url='" . $clickUrl . "'>";
```

**New way — attribute escaping is automatic, content escaping is explicit:**

```php
// Attributes: escaped automatically — no flags, no charset, no risk
$button = '<button ' . HtmlBuilder::attributes([
    'data-name' => $userInput,
    'data-url'  => $clickUrl,
]) . '>';

// Tag content: call text() for user-supplied strings — escaping is visible at the call site
echo HtmlBuilder::tag(
    'div',
    ['class' => 'alert alert-info'],
    HtmlBuilder::text($userMessage)     // <-- your responsibility, made explicit
);

// Tag content: pass trusted HTML directly — text() would double-escape rendered markup
echo HtmlBuilder::tag('div', ['class' => 'content'], $renderedHtmlBlock);
```

This is not a convenience feature. It is an architectural contract: the library carries the burden for attributes — the source of most real-world XSS — and makes content escaping an explicit, visible call rather than a discipline that can be silently forgotten.

---

## Table of Contents

0. [Security by Design](#security-by-design)
1. [Installation](#1-installation)
2. [URL Generation — Never Concatenate Again](#2-url-generation) · [Tier 2](#architecture-at-a-glance)
3. [Path Resolution — Cross-Platform, Always Correct](#3-path-resolution) · [Tier 2](#architecture-at-a-glance)
4. [Configuration — Dot Notation, Zero Globals](#4-configuration) · [Tier 2](#architecture-at-a-glance)
5. [Array Superpowers — Dot Notation for Everything](#5-array-superpowers) · [Tier 0](#architecture-at-a-glance)
6. [String Utilities — Slugs, Validation, Case Conversion](#6-string-utilities) · [Tier 0](#architecture-at-a-glance)
7. [Number Formatting — Human-Readable Everything](#7-number-formatting) · [Tier 0](#architecture-at-a-glance)
8. [HTML Builder — XSS Eliminated by Design](#8-html-builder) · [Tier 0](#architecture-at-a-glance)
9. [Collections — Fluent Data Transformation](#9-collections) · [Tier 0](#architecture-at-a-glance)
10. [Pipeline — Clean Data Processing](#10-pipeline) · [Tier 0](#architecture-at-a-glance)
11. [Fluent Strings — Chain Everything](#11-fluent-strings) · [Tier 0](#architecture-at-a-glance)
12. [Date Utilities — Testable Time](#12-date-utilities) · [Tier 0](#architecture-at-a-glance)
13. [File Operations — JSON, MIME, Zip in One Line](#13-file-operations) · [Tier 0](#architecture-at-a-glance)
14. [Caching — Multi-Tier, Zero Config](#14-caching) · [Tier 2](#architecture-at-a-glance) / [Tier 3](#architecture-at-a-glance)
15. [Error Recovery — Retry and Rescue](#15-error-recovery) · [Tier 0](#architecture-at-a-glance)
16. [Environment Detection](#16-environment-detection) · [Tier 0](#architecture-at-a-glance)
17. [Smarty Template Plugins](#17-smarty-template-plugins) · [Tier 4](#architecture-at-a-glance)
18. [Benchmarking — Know What's Slow](#18-benchmarking) · [Tier 0](#architecture-at-a-glance)
19. [Testing — Everything is Mockable](#19-testing)

---

## 1. Installation

```bash
composer require xoops/helpers
```

That's it. No configuration files. No bootstrap calls. No service registration. It just works.

```php
use Xoops\Helpers\Service\Path;

echo Path::base();  // Outputs XOOPS_ROOT_PATH immediately
```

**Optional global function shortcuts** — `collect()`, `str()`, `pipeline()`, `tap()`, `retry()`, `env()`, etc. are available via `src/functions.php` but are not auto-loaded. Opt in explicitly.

The recommended pattern is to load the file **once per request** in your XOOPS bootstrap rather than in individual module files. This prevents redundant `require` calls when multiple modules use the helpers in the same request:

```php
// Best: add once to mainfile.php or a central preload script
if (file_exists(XOOPS_ROOT_PATH . '/vendor/xoops/helpers/src/functions.php')) {
    require_once XOOPS_ROOT_PATH . '/vendor/xoops/helpers/src/functions.php';
}
```

If you are building a single module and do not control the bootstrap:

```php
// Per-module fallback — safe due to function_exists() guards in the file
require_once 'vendor/xoops/helpers/src/functions.php';
```

---

## Relationship with XMF 2.0

XOOPS Helpers is designed to be a **companion** to XMF 2.0 (`xoops/xmf`), not a replacement. They occupy different layers:

| Concern | XOOPS Helpers (`xoops/helpers`) | XMF 2.0 (`xoops/xmf`) |
|---------|--------------------------------|------------------------|
| **Purpose** | Low-level developer utilities | Architectural framework |
| **Namespace** | `Xoops\Helpers` | `Xmf` |
| **Dependency direction** | XMF depends on Helpers | Helpers never import XMF |
| **Array manipulation** | `Arr::get()`, `pluck()`, `groupBy()` | None (no utility classes) |
| **String processing** | `Str::slug()`, `camel()`, `isEmail()` | `FilterInput` (XSS only) |
| **Number formatting** | `Number::fileSize()`, `forHumans()`, `ordinal()` | None |
| **Collections** | `Collection` (fluent array wrapper) | None |
| **HTML construction** | `HtmlBuilder::attributes()`, `classes()` | `Presentation` (object-oriented UI) |
| **Pipeline** | Data transformation chains | HTTP middleware chains |
| **Cache** | Simple static facade, auto-detection | `CacheManager` with tags, backends, module scoping |
| **Config** | Static `Config::get('module.key')` | `ConfigManager` with schema validation |
| **Value objects** | None | `Slug`, `Email`, `Money`, `DateRange`, etc. |
| **DI Container** | Injectable facades via `use()`/`reset()` | Full `Container` (Symfony-style) |
| **Database** | None | Repository, QueryBuilder, Migrations |
| **Events** | None | EventBus, PSR-14 dispatch |

**There are only two minor overlaps:**

1. **JSON validation** — `Str::isJson()` and `XmfJsonHelper::isValid()` use the same logic. The Helpers version is a convenience wrapper; XMF's is the authoritative implementation.

2. **Random string generation** — `Str::random()` produces URL-safe random strings (for tokens, API keys). XMF's `Random::generateKey()` produces hash-based tokens (for CSRF). Different use cases, both needed.

**Everything else is complementary.** XMF 2.0 builds the architecture; Helpers provide the day-to-day conveniences that make XOOPS code shorter and safer.

---

## Migrating from XMF 1.x

You do not need to refactor existing XMF 1.x code before adopting this library. Both coexist safely with zero namespace conflicts. The decision of when to migrate a given file is straightforward:

**Starting a new module** — use XOOPS Helpers exclusively. There is no legacy to consider and you get automatic escaping, dot-notation config, and fluent collections from day one.

**Actively developing an existing module** — use XOOPS Helpers for all new code. When you open an existing file to add a feature, convert the XMF 1.x patterns in that file as you go. Do not schedule a dedicated refactoring sprint — let the migration happen organically when you have a reason to touch the file anyway.

**Maintaining a stable module** — do nothing. The libraries coexist. Migration cost is not justified by a pure maintenance ticket. Wait until the file needs to be opened for another reason.

The most common migration is the cache pattern, which also reduces line count the most:

```php
// XMF 1.x — before (5 lines, null-check pattern)
if (!$moduleConfig = \XoopsCache::read("{$dirname}_config")) {
    $moduleConfig = xoops_getModuleConfig($dirname);
    \XoopsCache::write("{$dirname}_config", $moduleConfig);
}

// XOOPS Helpers — after (1 line, same behavior)
$moduleConfig = Cache::remember("{$dirname}_config", 3600, fn() => xoops_getModuleConfig($dirname));
```

The config accessor pattern is the second most common:

```php
// XMF 1.x — before
$helper   = \XoopsModules\Mymod\Helper::getInstance();
$perPage  = $helper->getConfig('items_per_page');
$showTags = $helper->getConfig('show_tags');

// XOOPS Helpers — after
$perPage  = Config::get('mymod.items_per_page');
$showTags = Config::get('mymod.show_tags');
```

URL generation is the third, and it also closes a subtle encoding gap. XMF 1.x's `$helper->url()` is module-scoped and does not encode query parameters — query strings assembled alongside it by hand often skip `http_build_query()`:

```php
// XMF 1.x — before (query string built by hand, no parameter encoding)
$helper  = \XoopsModules\Mymod\Helper::getInstance();
$baseUrl = $helper->url('article.php');
$pageUrl = $baseUrl . '?id=' . $id . '&cat=' . $cat . '&sort=' . $sort;

// XOOPS Helpers — after (parameters encoded automatically)
$pageUrl = Url::module('mymod', 'article.php', [
    'id'   => $id,
    'cat'  => $cat,
    'sort' => $sort,
]);
```

---

## 2. URL Generation

### The Old Way

Real code from the XOOPS Core and modules:

```php
// XoopsCore25/htdocs/kernel/module.php:225
$ret = '<a href="' . XOOPS_URL . '/modules/' . $this->getVar('dirname') . '/">'
     . $this->getVar('name') . '</a>';

// XoopsCore25/htdocs/include/comment_post.php:490  (116 characters of concatenation!)
$comment_tags['X_COMMENT_URL'] = XOOPS_URL . '/modules/' . $not_module->getVar('dirname')
    . '/' . $comment_url . '=' . $com_itemid . '&amp;com_id=' . $newcid
    . '&amp;com_rootid=' . $com_rootid . '&amp;com_mode=' . $com_mode
    . '&amp;com_order=' . $com_order . '#comment' . $newcid;

// yogurt module - config/paths.php:9-13
'modPath'    => XOOPS_ROOT_PATH . '/modules/' . $moduleDirName,
'modUrl'     => XOOPS_URL . '/modules/' . $moduleDirName,
'uploadPath' => XOOPS_UPLOAD_PATH . '/' . $moduleDirName,
'uploadUrl'  => XOOPS_UPLOAD_URL . '/' . $moduleDirName,

// XoopsCore25/htdocs/Frameworks/art/functions.admin.php:72
$adminmenu_text .= '<li><a href="' . XOOPS_URL . '/modules/system/admin.php?fct=preferences'
    . '&op=showmod&mod=' . $GLOBALS['xoopsModule']->getVar('mid') . '"><span>'
    . _PREFERENCES . '</span></a></li>';
```

**Problems:**
- `rtrim()` / `ltrim()` everywhere to handle trailing slashes
- Query string building by hand — no encoding, easy to miss `&`
- The same pattern repeated 25+ times across the XOOPS Core alone

### The New Way

```php
use Xoops\Helpers\Service\Url;

// Module page with query parameters
$commentUrl = Url::module($dirname, $comment_url, [
    'com_itemid' => $com_itemid,
    'com_id'     => $newcid,
    'com_rootid' => $com_rootid,
    'com_mode'   => $com_mode,
    'com_order'  => $com_order,
]);

// Static asset
$css = Url::asset('themes/starter/css/style.css');

// Theme resource
$logo = Url::theme('starter', 'images/logo.png');

// Admin preferences link
$prefsUrl = Url::to('modules/system/admin.php', [
    'fct' => 'preferences',
    'op'  => 'showmod',
    'mod' => $xoopsModule->getVar('mid'),
]);

// Redirect — clean and readable
redirect_header(Url::module($dirname, 'error.php'), 3, $e->getMessage());
```

**Lines saved per module:** 25-50 URL concatenations replaced with one-liners.

---

## 3. Path Resolution

### The Old Way

```php
// XoopsCore25/htdocs/kernel/block.php:602-608 — 7 path concatenations just to load a block
if (file_exists(XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/blocks/' . $this->getVar('func_file'))) {
    if (file_exists(XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php')) {
        include_once XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/' . $GLOBALS['xoopsConfig']['language'] . '/blocks.php';
    } elseif (file_exists(XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/english/blocks.php')) {
        include_once XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/language/english/blocks.php';
    }
    include_once XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/blocks/' . $this->getVar('func_file');
}

// XoopsCore25/htdocs/Frameworks/moduleclasses/moduleadmin/moduleadmin.php:573-581 — language file with 3 fallback paths
$file = XOOPS_ROOT_PATH . "/modules/{$module_dir}/language/{$language}/changelog.txt";
if (!is_file($file) && ('english' !== $language)) {
    $file = XOOPS_ROOT_PATH . "/modules/{$module_dir}/language/english/changelog.txt";
}
if (!is_readable($file)) {
    $file = XOOPS_ROOT_PATH . "/modules/{$module_dir}/docs/changelog.txt";
}

// XoopsCore25/htdocs/include/notification_functions.php:176-178
if (!is_dir($dir = XOOPS_ROOT_PATH . '/modules/' . $module->getVar('dirname') . '/language/' . $xoopsConfig['language'] . '/mail_template/')) {
    $dir = XOOPS_ROOT_PATH . '/modules/' . $module->getVar('dirname') . '/language/english/mail_template/';
}
```

**Problems:**
- The string `XOOPS_ROOT_PATH . '/modules/' . $this->getVar('dirname')` appears **7 times** in one code block
- Forward slash vs `DIRECTORY_SEPARATOR` inconsistency across the codebase
- Every developer re-invents path joining with different slash-trimming strategies

### The New Way

```php
use Xoops\Helpers\Service\Path;

$dirname = $this->getVar('dirname');

// Block file
if (file_exists(Path::module($dirname, 'blocks/' . $funcFile))) {
    // Language file with fallback
    $langFile = Path::module($dirname, "language/{$language}/blocks.php");
    if (!file_exists($langFile)) {
        $langFile = Path::module($dirname, 'language/english/blocks.php');
    }
    if (file_exists($langFile)) {
        include_once $langFile;
    }
    include_once Path::module($dirname, 'blocks/' . $funcFile);
}

// All the standard paths — zero thinking required
Path::base();                      // XOOPS_ROOT_PATH
Path::storage();                   // XOOPS_VAR_PATH
Path::uploads();                   // XOOPS_UPLOAD_PATH
Path::modules('news');             // XOOPS_ROOT_PATH/modules/news
Path::themes('starter');           // XOOPS_ROOT_PATH/themes/starter
Path::module('news', 'language');  // XOOPS_ROOT_PATH/modules/news/language
Path::theme('starter', 'css');     // XOOPS_ROOT_PATH/themes/starter/css
```

Slashes, separators, trailing slashes — all handled automatically.

---

## 4. Configuration

### The Old Way

```php
// XoopsCore25/htdocs/include/common.php:222-243 — $GLOBALS['xoopsConfig'] 4 times in 20 lines
if (!empty($GLOBALS['xoopsConfig']['usercookie'])) {
    // ...
    xoops_setcookie($GLOBALS['xoopsConfig']['usercookie'], null, time() - 3600, '/', XOOPS_COOKIE_DOMAIN, 0, true);
    xoops_setcookie($GLOBALS['xoopsConfig']['usercookie'], null, time() - 3600);
}

// Inconsistent access patterns across the codebase:
$language = $GLOBALS['xoopsConfig']['language'];                                    // no empty check
$language = empty($GLOBALS['xoopsConfig']['language']) ? 'english' : $GLOBALS['xoopsConfig']['language'];  // with fallback

// XoopsCore25/htdocs/include/comment_view.php — $xoopsModuleConfig 8+ times
if (XOOPS_COMMENT_APPROVENONE != $xoopsModuleConfig['com_rule']) { ... }
if (!empty($xoopsModuleConfig['com_anonpost']) || is_object($xoopsUser)) { ... }

// wggallery module — helper->getConfig() repeated per-line
$GLOBALS['xoopsTpl']->assign('panel_type', $helper->getConfig('panel_type'));
$GLOBALS['xoopsTpl']->assign('show_breadcrumbs', $helper->getConfig('show_breadcrumbs'));
$GLOBALS['xoopsTpl']->assign('displayButtonText', $helper->getConfig('displayButtonText'));
```

### The New Way

```php
use Xoops\Helpers\Service\Config;

// System config — just works, with safe defaults
$language = Config::get('system.language', 'english');
$debug    = Config::get('system.debug_mode');
$cookie   = Config::get('system.usercookie');

// Module config — same API, auto-loaded from DB
$comRule  = Config::get('comments.com_rule');
$anonPost = Config::get('comments.com_anonpost');

// Bulk assign to template
foreach (['panel_type', 'show_breadcrumbs', 'displayButtonText'] as $key) {
    $xoopsTpl->assign($key, Config::get("wggallery.{$key}"));
}

// Check existence
if (Config::has('news.custom_template')) { ... }

// Get all module config
$allConfig = Config::all('news');
```

One API. Dot notation. Auto-cached. No globals.

---

## 5. Array Superpowers

### The Old Way

```php
// XMF-Final xmfblog module — admin/category.php:50-58
// Building lookup maps from handler results
$allCategories = $categoryRepo->findAll();
$categoryMap = [];
foreach ($allCategories as $cat) {
    $categoryMap[(int) $cat->getVar('category_id')] = [
        'name'      => (string) $cat->getVar('name'),
        'parent_id' => (int) $cat->getVar('parent_id'),
        'weight'    => (int) $cat->getVar('weight'),
    ];
}

// XoopsCore25/htdocs/include/findusers.php:289 — array_map + implode for SQL
$sql = 'SELECT u.* FROM ' . $this->db->prefix('users') . ' AS u'
    . ' LEFT JOIN ' . $this->db->prefix('groups_users_link') . ' AS g ON g.uid = u.uid'
    . ' WHERE g.groupid IN (' . implode(', ', array_map('intval', $groups)) . ')';

// Deep nested access with fallback — common in module settings
$value = isset($config['section']['subsection']['key'])
    ? $config['section']['subsection']['key']
    : 'default';
```

### The New Way

```php
use Xoops\Helpers\Utility\Arr;

// Deep nested access — one line
$value = Arr::get($config, 'section.subsection.key', 'default');

// Build option arrays from data
$names = Arr::pluck($users, 'uname', 'uid');
// => [1 => 'admin', 2 => 'john', 3 => 'jane']

// Filter, group, sort
$activeUsers = Arr::where($users, 'status', 'active');
$byRole      = Arr::groupBy($users, 'role');
Arr::sortBy($articles, 'date_created');

// Whitelist / blacklist keys
$safeData = Arr::only($_POST, ['title', 'body', 'category_id']);
$public   = Arr::except($userData, ['password', 'email', 'ip']);

// Check multiple keys at once
if (Arr::has($formData, ['title', 'body', 'author_id'])) {
    // all required fields present
}

// Flatten nested config to dot notation (and back)
$flat   = Arr::dot($nestedConfig);   // ['db.host' => 'localhost', 'db.port' => 3306]
$nested = Arr::undot($flat);          // ['db' => ['host' => 'localhost', 'port' => 3306]]
```

---

## 6. String Utilities

### The Old Way

```php
// wgtransifex module — admin/resources.php:118-124  (manual slug, 4 lines)
$slug = \preg_replace('~[^\pL\d]+~u', '', $res_name);
$slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
$slug = \preg_replace('~[^-\w]+~', '', $slug);
$slug = strtolower($slug);

// XoopsCore25/htdocs/class/xoopsform/formselectuser.php:157 — URL building inside onclick
$searchUsers->setExtra(' onclick="openWithSelfMain(\''
    . XOOPS_URL . '/include/findusers.php?target=' . $name
    . '&amp;multiple=' . $multiple . '&amp;token=' . $token
    . '\', \'userselect\', 800, 600, null); return false;" ');

// XoopsCore25 — manual base64 URL encoding (Utility.php)
public static function string_base64_url_encode($input) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($input));
}
```

**4 lines for a slug. 6 concatenations for a URL in an onclick. 3 lines for base64url.**

### The New Way

```php
use Xoops\Helpers\Utility\Str;
use Xoops\Helpers\Utility\Encoding;

// Slug — one line, handles Unicode via intl extension
$slug = Str::slug('Willkommen bei XOOPS');  // => "willkommen-bei-xoops"

// Base64 URL encoding — for tokens, JWT, etc.
$token = Encoding::base64UrlEncode($data);
$data  = Encoding::base64UrlDecode($token);

// Validation — clear method names
Str::isEmail('user@example.com');   // true
Str::isUrl('https://xoops.org');    // true
Str::isIp('192.168.1.1');           // true
Str::isJson('{"valid": true}');     // true
Str::isHexColor('#FF5733');         // true

// Case conversion
Str::camel('module_config');   // "moduleConfig"
Str::snake('moduleConfig');    // "module_config"
Str::studly('module_config');  // "ModuleConfig"
Str::kebab('moduleConfig');    // "module-config"

// String inspection
Str::contains($body, ['spam', 'phishing'], ignoreCase: true);
Str::startsWith($path, '/admin/');
Str::endsWith($filename, ['.jpg', '.png', '.gif']);

// Truncate safely (UTF-8)
Str::limit($article->getVar('body'), 150);  // "First 150 chars..."

// Mask sensitive data
Str::mask('user@example.com', '*', 4, 7);  // "user*******le.com"

// Cryptographically secure random strings
$apiKey = Str::random(32);
```

---

## 7. Number Formatting

### The Old Way

There's no standard file size formatter in XOOPS Core. Every module that needs one writes its own:

```php
// Common pattern across XOOPS modules (10 lines per module)
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// wgtimelines module — class/RatingsHandler.php:115
$ItemRating['avg_rate_value'] = number_format($current_rating / $count, 2);

// No ordinal formatting, no human-readable numbers, no locale-aware currency
```

### The New Way

```php
use Xoops\Helpers\Utility\Number;

// File sizes — one line
Number::fileSize(1572864);       // "1.50 MB"
Number::fileSize(2147483648);    // "2.00 GB"

// Human-readable large numbers
Number::forHumans(1500);         // "1.5K"
Number::forHumans(2300000);      // "2.3M"
Number::forHumans(1000000000);   // "1.0B"

// Ordinals (handles the 11th/12th/13th edge case correctly)
Number::ordinal(1);   // "1st"
Number::ordinal(2);   // "2nd"
Number::ordinal(3);   // "3rd"
Number::ordinal(11);  // "11th"
Number::ordinal(21);  // "21st"

// Locale-aware formatting (with intl extension)
Number::format(1234567, locale: 'de_DE');    // "1.234.567"
Number::percentage(75.5, 1, 'en_US');        // "75.5%"
Number::currency(99.99, 'EUR', 'de_DE');     // "99,99 EUR"

// Clamp to range
Number::clamp($userInput, min: 1, max: 100);
```

---

## 8. HTML Builder

This is the one that prevents security bugs.

### The Old Way

```php
// XoopsCore25/htdocs/banners.php — htmlspecialchars repeated 5 times in one file
htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
htmlspecialchars($imageurl, ENT_QUOTES | ENT_HTML5, 'UTF-8')
htmlspecialchars($clickurl, ENT_QUOTES | ENT_HTML5, 'UTF-8')

// XoopsCore25/htdocs/custom_blocks/example_welcome.php:39
$uname = htmlspecialchars($xoopsUser->getVar('uname', 'n'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

// XoopsCore25/htdocs/class/xoopsform/renderer/XoopsFormRendererLegacy.php:176
// 235-character inline concatenation mixing URL, JS, and security token:
$button = "<button type='button' class='btn btn-primary' onclick=\"form_instantPreview('"
    . XOOPS_URL . "', '" . $element->getName() . "','" . XOOPS_URL . "/images', "
    . (int) $element->doHtml . ", '" . $GLOBALS['xoopsSecurity']->createToken()
    . "')\" title='" . _PREVIEW . "'>" . _PREVIEW . "</button>";

// wgtimelines module — class/Items.php:128  (inline JS with escaped quotes)
$imageSelect->setExtra("onchange='showImgSelected(\"image1\", \"item_image\", \""
    . $imageDirectory . '", "", "' . \XOOPS_URL . "\")'");
```

**Every attribute, every value, every time — `htmlspecialchars($x, ENT_QUOTES | ENT_HTML5, 'UTF-8')`.** Miss it once and you have an XSS vulnerability. The `htmlspecialchars` call appears **30+ times** across the XOOPS Core alone.

### The New Way

```php
use Xoops\Helpers\Utility\HtmlBuilder;

// Attributes — auto-escaped, boolean support, null filtering
echo HtmlBuilder::attributes([
    'class'       => 'btn btn-primary',
    'data-id'     => $userInput,     // auto-escaped
    'disabled'    => $isDisabled,    // true => "disabled", false => omitted
    'data-config' => null,           // null => omitted
    'title'       => 'Click "here"', // quotes escaped automatically
]);
// => class="btn btn-primary" data-id="safe&amp;value" disabled title="Click &quot;here&quot;"

// Conditional CSS classes — the pattern every Bootstrap module needs
echo HtmlBuilder::classes([
    'btn',                           // always included
    'btn-primary' => $isPrimary,     // included when true
    'btn-lg'      => $isLarge,       // included when true
    'disabled'    => $isDisabled,    // included when true
]);
// => "btn btn-primary btn-lg"

// Complete tags — user-supplied text must go through text()
echo HtmlBuilder::tag('div', ['class' => 'alert alert-info'], HtmlBuilder::text($message));
// => <div class="alert alert-info">Your message here (safely escaped)</div>

// Trusted HTML block — e.g. output already produced by another HtmlBuilder call
echo HtmlBuilder::tag('div', ['class' => 'card-body'], $renderedHtmlBlock);
// => <div class="card-body">[trusted inner HTML, not double-escaped]</div>

// Self-closing tags
echo HtmlBuilder::tag('input', ['type' => 'text', 'name' => 'q', 'value' => $query], selfClose: true);
// => <input type="text" name="q" value="safe&amp;value" />

// Convenience methods
echo HtmlBuilder::stylesheet('/css/style.css');
echo HtmlBuilder::script('/js/app.js', ['defer' => true]);
echo HtmlBuilder::meta(['name' => 'description', 'content' => $description]);
```

**XSS split by design.** Attribute values and class names are escaped automatically — you
cannot forget those. Tag content is your responsibility: use `HtmlBuilder::text($value)` for
user-supplied strings, and pass trusted HTML blocks (rendered markup, translated strings that
have already been escaped upstream) directly.

---

## 9. Collections

### The Old Way

```php
// XMF-Final xmfblog — blocks/blog_blocks.php:54-62
// The same foreach-getVar-build-array pattern, repeated hundreds of times across XOOPS
$block = ['posts' => []];
foreach ($posts as $post) {
    $block['posts'][] = [
        'id'      => $post->getVar('post_id'),
        'title'   => $post->getVar('title'),
        'excerpt' => $post->getVar('excerpt')
            ?: mb_substr((string) $post->getVar('body'), 0, 100) . '...',
        'date'    => formatTimestamp((int) $post->getVar('date_created'), 's'),
        'views'   => $post->getVar('view_count'),
    ];
}

// wggallery module — index.php:43-50
foreach ($atoptions as $atoption) {
    $GLOBALS['xoopsTpl']->assign($atoption['name'], $atoption['value']);
    if ('number_cols_album' === $atoption['name']) {
        $number_cols_album = $atoption['value'];
    }
    if ('number_cols_cat' === $atoption['name']) {
        $number_cols_cat = $atoption['value'];
    }
}
```

### The New Way

```php
use Xoops\Helpers\Utility\Collection;
use Xoops\Helpers\Integration\XoopsCollection;

// From handler results — fluent transformation
$block['posts'] = XoopsCollection::fromHandler($postHandler, $criteria)
    ->map(fn($post) => [
        'id'      => $post->getVar('post_id'),
        'title'   => $post->getVar('title'),
        'excerpt' => $post->getVar('excerpt')
            ?: Str::limit((string) $post->getVar('body'), 100),
        'date'    => formatTimestamp((int) $post->getVar('date_created'), 's'),
        'views'   => $post->getVar('view_count'),
    ])
    ->toArray();

// Extract a config lookup in one line
$configValues = Collection::make($atoptions)->pluck('value', 'name')->all();
$number_cols_album = $configValues['number_cols_album'] ?? null;

// Chain operations fluently
$topAuthors = Collection::make($articles)
    ->groupBy('author_id')
    ->map(fn($group) => count($group))
    ->sortBy(fn($count) => $count, descending: true)
    ->take(10)
    ->all();

// Aggregation
$stats = [
    'total'   => $orders->count(),
    'revenue' => $orders->sum('amount'),
    'average' => $orders->avg('amount'),
];
```

---

## 10. Pipeline

Note: This is a **data transformation pipeline**, completely separate from XMF 2.0's `Xmf\Http\Pipeline` which is an HTTP middleware chain. Different purpose, no overlap.

### The Old Way

```php
// Typical input processing — nested, hard to read
$clean = htmlspecialchars(strip_tags(trim($rawInput)), ENT_QUOTES, 'UTF-8');

// Multi-step data transformation
$result = $data;
$result = array_filter($result, fn($item) => $item['active']);
$result = array_map(fn($item) => $item['name'], $result);
$result = array_unique($result);
sort($result);
```

### The New Way

```php
use Xoops\Helpers\Utility\Pipeline;

// Reads top to bottom instead of inside-out
$clean = Pipeline::send($rawInput)
    ->pipe(fn($v) => trim($v))
    ->pipe(fn($v) => strip_tags($v))
    ->pipe(fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'))
    ->thenReturn();

// Batch processing
$result = Pipeline::send($rawFormData)
    ->through([
        [$validator, 'sanitize'],
        [$transformer, 'normalize'],
        [$formatter, 'format'],
    ])
    ->thenReturn();
```

---

## 11. Fluent Strings

```php
use Xoops\Helpers\Utility\Stringable;

// Reads left to right, each step crystal clear
$slug = Stringable::of($title)->trim()->slug()->toString();

// With the global helper (opt-in)
$slug = str($title)->trim()->slug()->toString();

// Conditional operations
$display = Stringable::of($username)
    ->trim()
    ->when($showUppercase, fn($s) => $s->upper())
    ->limit(20)
    ->toString();
```

---

## 12. Date Utilities

### The Old Way

```php
// wgtimelines module — class/Items.php:153
$itemDate = $this->isNew()
    ? \mktime(0, 0, 0, (int)date("m"), (int)date("d"), (int)date("Y"))
    : $this->getVar('item_date');

// wgtimelines module — rss.php:60
$tpl->assign('channel_lastbuild', \formatTimestamp(\time(), 'rss'));
```

### The New Way

```php
use Xoops\Helpers\Utility\Date;

// Date ranges — for reports, calendars
$days = Date::range('2025-01-01', '2025-01-31');

// Validation
Date::isValid('2025-13-01');   // false
Date::isValid('not-a-date');   // false

// Date math
Date::addDays('2025-01-01', 30);   // "2025-01-31"
Date::subDays('2025-03-01', 1);    // "2025-02-28"

// Quick checks
Date::isWeekend('2025-03-15');     // true (Saturday)
Date::isPast('2020-01-01');        // true
Date::isFuture('2030-01-01');      // true

// Reformat between formats
Date::reformat('15/06/2025', 'd/m/Y', 'Y-m-d');  // "2025-06-15"

// Age calculation
Date::age('1990-05-15');  // 35
```

---

## 13. File Operations

### The Old Way

```php
// XoopsCore25 — common pattern (no error handling)
$config = json_decode(file_get_contents($configFile), true);

// XoopsCore25/htdocs/class/zipdownloader.php:61
$data = fread($fp, filesize($filepath));

// XoopsCore25/htdocs/class/xoopsmailer.php:298
$this->setBody(fread($fd, filesize($path)));
```

### The New Way

```php
use Xoops\Helpers\Utility\Filesystem;

// JSON I/O — one line each, with error handling built in
$config = Filesystem::readJson($configFile);   // null on failure
Filesystem::putJson($path, $data);             // false on failure

// MIME detection
Filesystem::mimeType($uploadedFile);  // "image/jpeg"
Filesystem::isImage('photo.webp');    // true

// Directory operations
Filesystem::mkdir(Path::storage('caches/mymod'));
Filesystem::copyDirectory($source, $destination);
Filesystem::deleteDirectory($tempDir);
Filesystem::isWritableRecursive(Path::uploads());

// Zip operations — for module exports
Filesystem::zip(Path::module('news', 'data'), '/tmp/news-export.zip');
Filesystem::unzip('/tmp/import.zip', Path::storage('imports'));
```

---

## 14. Caching

Note: For simple per-request caching, use XOOPS Helpers' `Cache` facade. For production multi-backend caching with tag invalidation, use XMF 2.0's `CacheManager`. They complement each other.

### The Old Way

```php
// XoopsCore25/htdocs/admin.php:105-137 — the classic null-check pattern
if (!$items = XoopsCache::read($rssfile)) {
    // ... fetch from network ...
    XoopsCache::write($rssfile, $items, 86400);
}

// Frameworks/art/functions.config.php:38-40 — module config caching
if (!$moduleConfig = XoopsCache::read("{$dirname}_config")) {
    $moduleConfig = xoops_getModuleConfig($dirname);
    XoopsCache::write("{$dirname}_config", $moduleConfig);
}
```

### The New Way

```php
use Xoops\Helpers\Service\Cache;

// Compute-and-cache in one call
$items = Cache::remember('rss_feed', 86400, function () {
    return fetchRssFeed();
});

$moduleConfig = Cache::remember("{$dirname}_config", 3600, function () use ($dirname) {
    return xoops_getModuleConfig($dirname);
});

// Basic operations
Cache::set('key', $value, 3600);
$value = Cache::get('key');
Cache::forget('key');
```

---

## 15. Error Recovery

```php
use Xoops\Helpers\Utility\Retry;

// Retry with exponential backoff
$result = Retry::retry(
    times: 3,
    callback: fn($attempt) => callExternalApi($url),
    sleepMs: fn($attempt) => 100 * (2 ** ($attempt - 1)),
);

// Graceful fallback
$result = Retry::rescue(
    callback: fn() => riskyOperation(),
    default: 'safe fallback value',
);

// Guard clauses
use Xoops\Helpers\Utility\ThrowHelper;
ThrowHelper::throwIf($id < 1, \InvalidArgumentException::class, 'ID must be positive');
ThrowHelper::throwUnless($user->isAdmin(), \RuntimeException::class, 'Admin required');
```

---

## 16. Environment Detection

```php
use Xoops\Helpers\Utility\Environment;

if (Environment::isDevelopment()) {
    // show debug toolbar
}

$dbHost = Environment::get('XOOPS_DB_HOST', 'localhost');
$apiKey = Environment::require('STRIPE_SECRET_KEY');  // throws if missing
```

---

## 17. Smarty Template Plugins

Register all helper plugins with one call:

```php
use Xoops\Helpers\Integration\Smarty\PluginRegistrar;
PluginRegistrar::register($xoopsTpl);
```

Then in your templates (with XOOPS delimiters):

```smarty
<link rel="stylesheet" href="<{asset_url path='css/style.css'}>">
<p>File size: <{format_number value=$filesize type="filesize"}></p>
<p>Downloads: <{format_number value=$downloads type="human"}></p>
<div class="<{css_classes classes=$rowClasses}>">
```

---

## 18. Benchmarking

```php
use Xoops\Helpers\Utility\Benchmark;

$result = Benchmark::measure(function () use ($handler, $criteria) {
    return $handler->getObjects($criteria);
});
echo "Query took {$result['time_ms']}ms, used {$result['memory_bytes']} bytes";

// Compare approaches
$avg = Benchmark::average(fn() => directDbQuery(), iterations: 100);
echo "Average: {$avg['avg_ms']}ms (min: {$avg['min_ms']}ms, max: {$avg['max_ms']}ms)";
```

---

## 19. Testing

Every service is mockable. No globals required.

```php
use Xoops\Helpers\Service\{Path, Url, Config, Cache};
use Xoops\Helpers\Provider\ArrayCache;

class MyModuleTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::use(new ArrayCache());
        Config::registerLoader('mymod', fn() => ['items_per_page' => 10]);
    }

    protected function tearDown(): void
    {
        Path::reset();
        Url::reset();
        Cache::reset();
        Config::reset();
    }
}
```

---

## Quick Reference Card

| Task | Old Way | New Way |
|------|---------|---------|
| HTML attributes | Manual `htmlspecialchars` per attribute | `HtmlBuilder::attributes([...])` |
| Escape HTML | `htmlspecialchars($v, ENT_QUOTES\|ENT_HTML5, 'UTF-8')` | `HtmlBuilder::escape($v)` |
| CSS classes | Ternary concatenation | `HtmlBuilder::classes([...])` |
| Module URL | `XOOPS_URL.'/modules/'.$dir.'/page.php?id='.$id` | `Url::module($dir, 'page.php', ['id' => $id])` |
| Module path | `XOOPS_ROOT_PATH.'/modules/'.$dir.'/class'` | `Path::module($dir, 'class')` |
| Deep array access | `isset($a['x']['y']) ? $a['x']['y'] : 'def'` | `Arr::get($a, 'x.y', 'def')` |
| Module config | `global $xoopsModuleConfig; $xoopsModuleConfig['key']` | `Config::get('module.key')` |
| System config | `$GLOBALS['xoopsConfig']['sitename']` | `Config::get('system.sitename')` |
| Generate slug | 4-line preg_replace chain | `Str::slug($title)` |
| File size display | 10-line loop function | `Number::fileSize($bytes)` |
| JSON file read | `json_decode(file_get_contents(...), true)` | `Filesystem::readJson($path)` |
| Extract column | `foreach ($items as $i) { $out[] = $i['name']; }` | `Arr::pluck($items, 'name')` |
| Cache with fallback | 5-line if/read/write pattern | `Cache::remember($key, $ttl, $fn)` |
| Retry operation | 15-line while/try/catch loop | `Retry::retry(3, $callback, 500)` |
| Random string | `bin2hex(random_bytes($n / 2))` | `Str::random(32)` |

---

## Appendix: Other Useful Helpers

These utilities are part of the library but do not appear in the main sections above. They are compositional helpers — they support other patterns rather than being primary use cases on their own.

### Value — Resolve, Inspect, Guard

```php
use Xoops\Helpers\Utility\Value;

// Resolve a value that might be a Closure or a literal
$label = Value::value($config['label'] ?? fn() => xoops_getModuleInfo('name'));

// Blank/filled — smarter than empty(): 0 and false are NOT blank
Value::blank('');          // true
Value::blank('  ');        // true  (whitespace-only)
Value::blank(0);           // false (numeric values are never blank)
Value::blank(false);       // false (booleans are never blank)
Value::filled($username);  // true if not blank

// Null-safe property access — no more isset() chains
$email = Value::optional($user)?->getVar('email');
$city  = Value::optional($profile)?->getVar('city') ?? 'Unknown';

// Memoize an expensive call within a request
$siteConfig = Value::once(fn() => xoops_getModuleConfig('system'));

// Distinguish "key missing" from "key is null" in Arr::get()
$sentinel = Value::missing();
$val = Arr::get($data, 'optional_key', $sentinel);
if ($val instanceof \Xoops\Helpers\Utility\MissingValue) {
    // key was not present at all — different from $val === null
}
```

### Data — Convert Between Shapes

```php
use Xoops\Helpers\Utility\Data;

// stdClass response from an API or XoopsObject::toArray() alternative
$array  = Data::toArray($stdClassResponse);
$object = Data::toObject($configArray);

// Query strings — useful for building redirect URLs or API calls
$qs     = Data::toQueryString(['fct' => 'preferences', 'op' => 'showmod', 'mod' => $mid]);
// => "fct=preferences&op=showmod&mod=42"

$params = Data::fromQueryString($_SERVER['QUERY_STRING'] ?? '');
```

### Transform — Conditional Value Processing

```php
use Xoops\Helpers\Utility\Transform;

// Apply a callback only when the value is filled (not blank)
// Returns $default if blank — replaces common ternary patterns
$slug = Transform::transform(
    $article->getVar('custom_slug'),
    fn($s) => Str::slug($s),
    fn() => Str::slug($article->getVar('title'))  // default: generate from title
);

// Predicate-based branching in a chain
$display = Transform::when(
    $article->getVar('views'),
    fn($v) => $v > 1000,
    fn($v) => Number::forHumans($v),   // "1.2K"
    fn()   => '< 1K'
);
```

### Tap — Side Effects Without Breaking a Chain

```php
use Xoops\Helpers\Utility\Tap;
use Xoops\Helpers\Traits\Tappable;  // add tap() to any class

// Log or debug without interrupting a value chain
$user = Tap::tap(
    $userHandler->get($uid),
    fn($u) => xoops_error("Loaded user: " . $u->getVar('uname'))
);

// The Tappable trait adds ->tap() to any class you own
class ArticleRepository {
    use Tappable;

    public function findPublished(): static {
        // ... query ...
        return $this;
    }
}

$articles = (new ArticleRepository())
    ->findPublished()
    ->tap(fn($repo) => $logger->info("Query: {$repo->lastQuery()}"))
    ->sortBy('date');
```

### Path::languageFile() — Language Fallback in One Line

```php
use Xoops\Helpers\Service\Path;

// Old — 6 lines, path string repeated 4 times
$file = XOOPS_ROOT_PATH . '/modules/' . $dirname . '/language/' . $language . '/blocks.php';
if (!is_file($file) && $language !== 'english') {
    $file = XOOPS_ROOT_PATH . '/modules/' . $dirname . '/language/english/blocks.php';
}
if (is_file($file)) {
    include_once $file;
}

// New — two lines
$file = Path::languageFile($dirname, $language, 'blocks.php');
if (is_file($file)) { include_once $file; }

// Common language files by convention
$language = $GLOBALS['xoopsConfig']['language'] ?? 'english';
Path::languageFile($dirname, $language, 'main.php');
Path::languageFile($dirname, $language, 'blocks.php');
Path::languageFile($dirname, $language, 'admin.php');
```

---

## Architecture at a Glance

```mermaid
graph TD
    Module["**Your Module**
    use Xoops\\Helpers\\Service\\{Path, Url, Config, Cache}
    use Xoops\\Helpers\\Utility\\{Arr, Str, Number, Collection}
    use Xoops\\Helpers\\Utility\\{Pipeline, Stringable, HtmlBuilder}"]

    subgraph helpers[" xoops/helpers "]
        tiers["Tier 0 · Utility/
        Tier 1 · Contracts/
        Tier 2 · Service/
        Tier 3 · Provider/
        Tier 4 · Integration/"]
    end

    subgraph xmf[" xoops/xmf 2.0 "]
        xmfcontent["requires xoops/helpers
        ──────────────────────
        Repository · EventBus · Container
        QueryBuilder · CacheManager
        ConfigManager · Presentation"]
    end

    Core["**XOOPS Core**
    XOOPS_ROOT_PATH · XOOPS_URL · XoopsCache · XoopsObject"]

    Module      --> helpers
    Module      --> xmf
    xmf         -->|"declares as dependency"| helpers
    helpers     --> Core
    xmf         --> Core

    style Module     fill:#dbeafe,stroke:#3b82f6,color:#000
    style tiers      fill:#f0fdf4,stroke:#86efac,color:#000
    style helpers    fill:#dcfce7,stroke:#16a34a,color:#000
    style xmf        fill:#fef9c3,stroke:#ca8a04,color:#000
    style xmfcontent fill:#fef9c3,stroke:#ca8a04,color:#000
    style Core       fill:#fce7f3,stroke:#db2777,color:#000
```

**Tier 0 utilities work everywhere** — CLI scripts, cron jobs, migrations, standalone tools. No XOOPS boot required.

---

*All "Old Way" examples are from real XOOPS Core 2.5 and production module code — not experiments or prototypes.*

*XOOPS Helpers: 151 tests. 233 assertions. 41 source files. One `composer require`.*
