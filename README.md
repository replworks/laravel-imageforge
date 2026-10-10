This package is a client for the Image Forge service.

# Laravel ImageForge

[![code-style](https://github.com/replworks/laravel-imageforge/actions/workflows/code-style.yml/badge.svg)](https://github.com/replworks/laravel-imageforge/actions/workflows/code-style.yml)
[![tests](https://github.com/replworks/laravel-imageforge/actions/workflows/tests.yml/badge.svg)](https://github.com/replworks/laravel-imageforge/actions/workflows/tests.yml)
[![update-changelog](https://github.com/replworks/laravel-imageforge/actions/workflows/update-changelog.yml/badge.svg)](https://github.com/replworks/laravel-imageforge/actions/workflows/update-changelog.yml)

Laravel ImageForge is the Laravel client library for [Image Forge](https://if.repl.net), an image conversion service. It generates Image Forge URLs from image paths stored on Laravel models or from direct application code.

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

### Blade directives

For direct use in Blade templates, the package provides escaped directives:

```blade
@imageForgeAsset('assets/seo/og.png', w: 1200, h: 630, f: 'jpeg')
@imageForgeStorage('uploads/og.png', w: 1200, h: 630)
```

`@imageForgeAsset` uses Laravel's `asset()` Source URL, while `@imageForgeStorage` uses the configured storage disk. Both directives accept the same positional and named Options as `ImageForge::make()` and HTML-escape the rendered URL.

When a generated URL is captured in a section or variable and will be escaped later with Blade's `{{ }}` echo, use the corresponding raw directive:

```blade
@section('meta_image')
    @imageForgeAssetRaw('assets/seo/og.png', w: 1200, h: 630)
@endsection
```

The package also provides `@imageForgeStorageRaw()`. Raw directives do not HTML-escape their output. Use them only for trusted Image Forge URLs and escape the value once at its final HTML output location.

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

### Eloquent models

Add the `HasImageForge` Trait and declare the image attributes that should receive generated methods:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Replworks\ImageForge\Concerns\HasImageForge;

class Template extends Model
{
    use HasImageForge;

    protected array $imageForgeFields = [
        'preview_path',
        'thumbnail_path',
    ];
}
```

The declared fields become `previewPathImageForge()` and `thumbnailPathImageForge()`:

```php
$url = $template->previewPathImageForge(w: 320, h: 240)->storage();
```

Generated methods read the raw stored attribute value and do not require Eloquent casts.

### Eloquent Attribute accessors

You can also return an ImageForge Builder from a Laravel `Attribute` accessor without using the `HasImageForge` Trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Replworks\ImageForge\ImageForge;

class Template extends Model
{
    protected function previewPathImageForge(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) =>
                ImageForge::make($attributes['preview_path'] ?? null, w: 320),
        );
    }
}
```

Access the resulting Builder as an Eloquent attribute:

```php
$url = $template->preview_path_image_forge->storage();
```

This approach uses property access and configures the Builder inside the accessor. It does not provide a generated method that accepts per-call options like `previewPathImageForge(w: 320)`.

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
