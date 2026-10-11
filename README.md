This package is a client for the Image Forge service.

# Laravel ImageForge

[![code-style](https://github.com/replworks/laravel-imageforge/actions/workflows/code-style.yml/badge.svg)](https://github.com/replworks/laravel-imageforge/actions/workflows/code-style.yml)
[![tests](https://github.com/replworks/laravel-imageforge/actions/workflows/tests.yml/badge.svg)](https://github.com/replworks/laravel-imageforge/actions/workflows/tests.yml)
[![update-changelog](https://github.com/replworks/laravel-imageforge/actions/workflows/update-changelog.yml/badge.svg)](https://github.com/replworks/laravel-imageforge/actions/workflows/update-changelog.yml)

Laravel ImageForge is the Laravel client library for [Image Forge](https://if.repl.net), an image conversion service. It generates Image Forge URLs from Blade image classes or direct application code.

Image Forge is built in the REPLWorks way. The service and its making are introduced in the [REPLWorks Image Forge showcase](https://www.repl.net/showcase/imageforge/).

Official Image Forge service guidance and settings can be found at [if.repl.net](https://if.repl.net).

## Prerequisite

Before using this package, register your application's domain with Image Forge at [if.repl.net](https://if.repl.net). Use the service identifier issued for that registered domain as `IMAGEFORGE_SERVICE` in your Laravel application.

## Requirements

- PHP 8.3 or later
- Laravel 12.x

## Installation

Install the package with Composer:

```sh
composer require replworks/laravel-imageforge
```

Laravel discovers the package service provider automatically.

## Configuration

The package works without publishing its configuration file. Set the service identifier in your application environment:

```dotenv
IMAGEFORGE_SERVICE=wifinote
```

Optional settings are available through environment variables:

```dotenv
IMAGEFORGE_BASE_URL=https://img.repl.net
IMAGEFORGE_DISK=public
```

To publish the configuration file into the application:

```sh
php artisan vendor:publish --tag=imageforge-config
```

This creates `config/imageforge.php` with the following settings:

| Setting               | Environment variable  | Default                  |
| --------------------- | --------------------- | ------------------------ |
| `imageforge.base_url` | `IMAGEFORGE_BASE_URL` | `https://img.repl.net`   |
| `imageforge.service`  | `IMAGEFORGE_SERVICE`  | none                     |
| `imageforge.disk`     | `IMAGEFORGE_DISK`     | application default disk |
| `imageforge.dsl_prefix` | none                  | `if-`                    |

## Usage

Create a Builder with `ImageForge::make()`. The arguments are `path`, `w`, `h`, `fit`, `q`, and `f`, in that order. Named arguments are supported.

### Storage URLs

Call `storage()` to use the configured Laravel filesystem disk as the Source URL:

```php
use Replworks\ImageForge\ImageForge;

$url = ImageForge::make('templates/preview.png', 320)->storage();
// https://img.repl.net/wifinote/storage/templates/preview.png?w=320
```

When `w` is omitted, `storage()` returns the original storage URL:

```php
$url = ImageForge::make('templates/preview.png')->storage();
```

### Asset URLs

Call `asset()` to use Laravel's `asset()` URL as the Source URL:

```php
$url = ImageForge::make('templates/preview.png', 320)->asset();
// https://img.repl.net/wifinote/templates/preview.png?w=320
```

### Blade Utility Class DSL

For ordinary Blade images, add Utility Classes to the `<img>` element. The tag is scanned when Blade compiles the view, and the URL is resolved when the compiled view renders:

```blade
<img src="photo.jpg" class="rounded if-w-480 if-h-320 if-crop" alt="">
```

This renders an escaped URL like:

```html
<img src="https://img.repl.net/wifinote/photo.jpg?w=480&amp;h=320&amp;fit=fill" class="rounded" alt="">
```

Supported Utility Classes are:

| Utility Class | Image Forge option | Description |
| --- | --- | --- |
| `if-w-{0..2000}` | `w={n}` | Width; values are clamped to `0..2000`. |
| `if-h-{0..2000}` | `h={n}` | Height; values are clamped to `0..2000`. |
| `if-q-{1..100}` | `q={n}` | Quality; values are clamped to `1..100`. |
| `if-fit` | `fit=fit` | Fit without cropping. |
| `if-fill` | `fit=fill` | Fill the requested dimensions. |
| `if-crop` | `fit=fill` | Alias for `if-fill`. |
| `if-fit-auto` | `fit=auto` | Let Image Forge choose the fit behavior. |
| `if-webp` | `f=webp` | Request WebP output. |
| `if-avif` | `f=avif` | Request AVIF output. |
| `if-jpeg` | `f=jpeg` | Request JPEG output. |
| `if-png` | `f=png` | Request PNG output. |
| `if-auto` | omit `f` | Select the format from the browser's `Accept` header. |

Repeated options use the last class in source order. If any Utility Class is present without a width class, the DSL supplies `w=0`. The `src` value should already be the URL that correctly displays the image, for example `{{ asset($path) }}` or `{{ Storage::url($path) }}`; the DSL transforms that resolved source URL without guessing its origin.

The prefix can be changed in `config/imageforge.php`:

```php
'dsl_prefix' => 'media-',
```

Unknown prefixed classes remain in the `class` attribute. Absolute URLs, data URIs, protected template regions, and unsupported source expressions are left safe and unchanged.

### Options

Options are rendered in the order `w`, `h`, `fit`, `q`, `f`. Options with a `null` value are omitted.

```php
$url = ImageForge::make(
    'templates/preview.png',
    w: 320,
    h: 240,
    fit: 'fill',
    q: 80,
    f: 'webp',
)->storage();
// ...?w=320&h=240&fit=fill&q=80&f=webp
```

The package does not validate or encode option values.

`IMAGEFORGE_SERVICE` must be configured when a Forge URL is requested (`w` is present).

An empty image path (`null` or `''`) returns `null` from both `storage()` and `asset()`.

## Testing

Install development dependencies and run the test suite:

```sh
composer install
composer test
```

Format PHP files with Laravel Pint:

```sh
composer format
```

## License

This package is open-sourced under the [MIT License](LICENSE).
