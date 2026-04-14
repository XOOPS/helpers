# Contributing

## Scope

XOOPS Helpers aims to stay small, dependency-light, and compatible with PHP 8.2 through 8.5. New helpers should be broadly reusable and justified by repeated XOOPS development needs.

## Development workflow

1. Create a feature branch from `master`.
2. Keep changes focused on one bugfix or feature.
3. Add or update tests for every behavior change.
4. Run the local checks before opening a pull request.

## Local checks

```bash
composer validate --strict
composer test
composer analyse
```

## Coding expectations

- Use `declare(strict_types=1)` in PHP files.
- Prefer explicit types and small, composable methods.
- Keep pure utilities framework-agnostic when possible.
- Avoid breaking public APIs without documenting the change in `CHANGELOG.md`.
- Add integration tests for XOOPS- or Smarty-specific behavior.

## Pull requests

- Explain the problem, the approach, and any compatibility impact.
- Link related issues when available.
- Call out BC breaks explicitly.
