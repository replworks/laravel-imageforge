# TECH_STACK

## 1. Languages / runtimes / frameworks / libraries

- Language: PHP, version constraint `^8.3`.
- Framework: Laravel, version constraint `^12.0`.
- Package manager: composer.
- Runtime dependency `illuminate/support` `^12.0`. Reason: service provider base class, Storage facade.
- Runtime dependency `illuminate/contracts` `^12.0`. Reason: config repository contract.
- Runtime dependency `illuminate/filesystem` `^12.0`. Reason: storage disk manager that provides disk URLs.
- Runtime dependency `illuminate/routing` `^12.0`. Reason: URL generator implementation behind `asset()`.
- Runtime dependency `illuminate/view` `^12.0`. Reason: registering the package's Blade precompiler.
- Development dependency `phpunit/phpunit` `^11.5.3 || ^12.0.1`. Reason: test runner, range accepted by Testbench 10.
- Development dependency `orchestra/testbench` `^10.0`. Reason: boots a Laravel application for package tests.
- Development dependency `laravel/pint`, latest 1.x release (`^1.0`). Reason: code formatter.
- Module system: PSR-4 autoload, namespace `Replworks\ImageForge\` mapped to `src/`.
- Test autoload: PSR-4 autoload-dev, namespace `Replworks\ImageForge\Tests\` mapped to `tests/`.

## 2. Framework- or tool-mandated paths and file names

- `composer.json` MUST exist at the repository root.
- `composer.lock` MUST NOT be committed.
- `config/imageforge.php` MUST be the config file name.
- `src/` MUST hold package source.
- `tests/` MUST hold tests.
- `phpunit.xml.dist` MUST be the PHPUnit config file name.
- `pint.json` MUST be the Pint config file name.
- `.github/workflows/` MUST hold GitHub Actions workflow files.
- `LICENSE` MUST exist at the repository root.
- The service provider MUST be registered under `extra.laravel.providers` in `composer.json`.
- The service provider class MUST be `Replworks\ImageForge\ImageForgeServiceProvider`.

## 3. Coding conventions specific to the chosen stack

- PHP code MUST be formatted with Laravel Pint using preset `laravel`.
- PHP files MUST NOT be required to contain `declare(strict_types=1)`.
- Classes MUST follow PSR-4 file and namespace mapping.

## 4. Testing rules

- Tests MUST use PHPUnit.
- Tests that need a Laravel application MUST extend `Orchestra\Testbench\TestCase`.
- Test files MUST be named `*Test.php`.
- Code coverage MUST NOT be measured or required.

## 5. Configuration rules

- Config values MUST be defined in `config/imageforge.php`.
- Environment variable names MUST be `IMAGEFORGE_BASE_URL`, `IMAGEFORGE_SERVICE`, `IMAGEFORGE_DISK`.
- `env()` MUST be called only inside `config/imageforge.php`.
- The package MUST NOT define, read, or store secrets.

## 6. Deployment rules

- Distribution target MUST be Packagist.
- Package name MUST be `replworks/laravel-imageforge`.
- Releases MUST be GitHub releases with semver tags in the format `vMAJOR.MINOR.PATCH`, for example `v1.0.0`.
- Packagist MUST update automatically when a GitHub release is published.
- CI MUST be GitHub Actions.
- The CI matrix MUST be PHP `8.3` x Laravel `12.*` x Testbench `10.*`.
- CI MUST resolve dependencies with `--prefer-stable`.
- CI MUST run PHPUnit.
- License MUST be MIT.

## 7. Security rules tied to the chosen stack

- None stated.

## 8. Non-goals

- Static analysis tools, including PHPStan and Larastan, MUST NOT be used.
- Code coverage measurement MUST NOT be used.
