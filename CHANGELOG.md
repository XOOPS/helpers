# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning where practical.

## [Unreleased]

### Security

- **`Utility\Filesystem::zip()`** — the containment check folded every backslash to a
  forward slash, so on POSIX a symlink inside the source tree pointing at a sibling such
  as `src\secret.txt` compared as `src/secret.txt` and the outside file was archived.
  Backslashes are now treated as separators (and case folded) only on Windows. On POSIX,
  files whose name contains a literal backslash are left out, since extracting on Windows
  would read it as a path separator. `zip()` also returns `false` when the source
  directory cannot be resolved.
- **`Provider\XoopsCacheAdapter`** — the file-cache fallback used when `XOOPS_VAR_PATH`
  is undefined lived in the shared `sys_get_temp_dir()/xmf_cache`, where another local
  account could pre-create the directory and plant entry symlinks that redirected cache
  writes. On POSIX it now uses `xmf_cache_<euid>` (mode 0700) and refuses a linked,
  foreign-owned or group/other-accessible directory; without ext-posix the file cache is disabled. Entries are
  written to an exclusively created temp file and renamed into place, so an existing
  symlink is replaced rather than written through. Entry names are now SHA-256 (was
  MD5) of the key, so every file-cache user sees a one-time cache miss.
- **Test suite** — the bootstrap now allocates a fresh private test root per run
  instead of the fixed `sys_get_temp_dir()/xoops_test`; filesystem and language-file
  fixtures live under it, and fixture setup fails loudly.

## [1.0.0] — 2026-07-22

First stable release. No breaking changes since 1.0.0 Beta2.

### Added

- **`Service\HtmlSanitizer`** — shared HTMLPurifier front-end replacing the per-module
  copy-paste that either disabled the definition cache (`Cache.DefinitionImpl => null`,
  forcing a ~40–60 ms HTML-definition rebuild on every request) or let the default
  serializer attempt writes inside `vendor/`. Definitions are serialized once into a
  writable directory resolved via `Path::storage('caches/htmlpurifier')` and load in
  ~1 ms afterwards; purifier instances are memoized per configuration for the rest of
  the request. `ezyang/htmlpurifier` is an optional dependency — when it is absent
  `purify()` and `purifier()` return `null` instead of throwing, so callers can fall
  back to their own sanitizer.

### Fixed

- **`Utility\Benchmark::average()`** — now throws `InvalidArgumentException` when
  `$iterations` is less than 1. Previously the averaging divided by zero and
  `min()`/`max()` on the empty sample array raised a `ValueError` on PHP 8.
- **`Utility\Filesystem::zip()`** — strips any trailing separator from the resolved base
  path, so a root base (`/` or `C:\`) no longer builds a doubled separator that skipped
  every file and produced an empty archive. Entries resolving outside the base directory
  (for example via an escaping symlink) are now skipped rather than added, and the prefix
  comparison normalises separators to forward slashes so a mixed-separator path on
  Windows cannot defeat the check.

## [1.0.0 Beta2] — 2026-06-16

### Added

- `Filesystem::copy()`, `Filesystem::delete()`, `Filesystem::move()`, `Filesystem::rename()` — single-file
  operations complementing the existing directory-level `copyDirectory()`/`deleteDirectory()`/`moveDirectory()`.
  `copy()`/`move()` create the destination directory when missing; `move()` falls back to copy-then-delete when
  `rename()` cannot cross filesystems; `delete()` treats an absent path as success and refuses real directories.
- `Filesystem::secureDir()` — creates a directory and writes an anti-listing `index.html` guard (the
  `history.go(-1)` redirect XOOPS modules otherwise write by hand after each `mkdir`).
- `Url::upload()` and `Url::moduleUpload()` — generate URLs under the uploads root, honoring the
  independently-configurable `XOOPS_UPLOAD_URL` (falling back to a site-rooted `uploads/` path only when it is
  undefined). `UrlGeneratorInterface` gains the matching `upload()` / `moduleUpload()` contract methods.
- `Path::moduleUpload()` — filesystem-path counterpart resolving a module's uploads subfolder under
  `XOOPS_UPLOAD_PATH`.
- Tests: `DefaultPathLocatorTest`, `DefaultUrlGeneratorTest`, and new cases in `FilesystemTest`, `UrlTest`,
  and `PathTest` covering the above.
- `HtmlBuilder::text(string $value): string` — semantic wrapper over `escape()` for inserting
  user-supplied plain text into tag bodies. Makes content escaping visible at the call site and
  distinguishes it from trusted HTML blocks passed directly to `tag()`.
- `Path::languageFile(string $dirname, string $language, string $file): string` — resolves the
  full path to a module language file, falling back to `english/` when the requested locale is
  absent, mirroring the pattern used throughout XOOPS Core.
- Mermaid architecture and ecosystem diagrams (`architecture.mermaid`, `ecosystem.mermaid`),
  replacing the ASCII diagram previously embedded in `TUTORIAL.md`.
- Direct test coverage for eleven previously untested classes:
  `Filesystem`, `Environment`, `Encoding`, `Retry`, `ThrowHelper`, `Transform`, `Tap`,
  `Stringable`, `AssetUrlPlugin`, `CssClassesPlugin`, and `FormatNumberPlugin`.
- Seven new tests in `HtmlBuilderTest` covering `text()`, its equivalence to `escape()`, XSS
  payloads, and the full `tag(…, HtmlBuilder::text($input))` integration pattern.
- Four new tests in `PathTest` covering `languageFile()`: primary found, English fallback,
  neither found (returns primary path), and English-language short-circuit.

### Changed

- `README.md` rewritten: `HtmlBuilder` example promoted to first position, security contract
  clarified (attributes auto-escaped; content is caller responsibility), Quick Start updated with
  `text()` and `languageFile()` examples, XMF 1.x migration decision tree added, optional
  `functions.php` bootstrap convention documented.
- `TUTORIAL.md` rewritten: new "Security by Design" section added before the TOC; all tag-body
  examples updated to use `HtmlBuilder::text()` for user-supplied strings; XMF 1.x migration
  section with three before/after diffs added; "Other Helpers" appendix covering `Value`, `Data`,
  `Transform`, `Tap`, and `Path::languageFile()` added; Mermaid ecosystem diagram replaces ASCII.
- `HtmlBuilder::tag()` `$content` parameter docblock tightened: now explicitly states content is
  not auto-escaped, directs callers to `text()` for user strings, and notes that trusted HTML
  should be passed directly to avoid double-escaping.
- `HtmlBuilder::escape()` docblock updated to point to `text()` as the preferred method for
  tag-body content contexts.
- `AssetUrlPlugin` `secure` parameter test coverage extended to cover `'yes'` (unrecognised →
  falls back to `false`) and `'1'` (recognised truthy → `true`), pinning the `filter_var`
  fallback behaviour as a specification.

### Fixed

- `DefaultPathLocator` now always emits forward slashes. It previously joined with `DIRECTORY_SEPARATOR`,
  producing backslashes on Windows that could not be compared against XOOPS's forward-slash path constants;
  paths are now normalized to `/` (which PHP accepts on every platform), so the `Path::*` helpers are safe for
  building path *string* values, not only for file I/O.
- `Filesystem::secureDir()` now returns `false` when the `index.html` guard cannot be written, instead of
  silently reporting success.
- `DefaultUrlGenerator` HTTPS forcing now preserves the query string and fragment (it previously rebuilt the
  URL from host/port/path only, discarding `?…`/`#…`).
- `Stringable::when()` now applies its callback on a *truthy* condition (matching its docblock). It previously
  used `Value::filled()`, which treats boolean `false` as "filled" and wrongly ran the callback for
  `when(false, …)`.
- `Filesystem::readChunked()` no longer emits a trailing empty chunk to the callback at end-of-file.
- `Filesystem::readJson()` returns `null` for a bare JSON list (e.g. `[1,2,3]`), honoring its
  `array<string, mixed>` object contract.
- Resolved 5 PHPStan 2.x findings surfaced once the analyser resolved to 2.x in CI: corrected
  `XoopsCollection::fromHandler()`'s `@param` to `object` (it duck-types via `method_exists`), relaxed the
  by-reference `array` phpdoc on `Arr::set()`/`Arr::forget()` (and the `arr_set()` wrapper), dropped a
  redundant `is_array()` in `Arr::collapse()`, and made `Collection` consume the `Tappable` trait (removing a
  duplicated `tap()` and the "trait used zero times" report). Pinned `phpstan/phpstan` to `^2.0` so local and
  CI analyse with the same major (the prior `^1.0 || ^2.0` + uncommitted lock let them drift).
- Resolved a fatal inheritance conflict in `XoopsCollection`.
- Hardened `Optional` so non-object method calls return `null` instead of throwing.
- Corrected `Arr::isAssoc([])` to return `false`.
- Added integration coverage for XOOPS collection helpers.
- `TUTORIAL.md` section 8 previously showed `$message` passed raw into `HtmlBuilder::tag()` then
  claimed escaping was automatic — corrected to `HtmlBuilder::text($message)` with accurate prose
  distinguishing attribute escaping (automatic) from content escaping (explicit).

### Infrastructure

- Added PHPStan configuration and XOOPS stubs for static analysis.
- Added repository health files, issue forms, PR template, and dependency/security workflows.
