# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning where practical.

## [Unreleased]

### Added

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
