# PRODUCT_SPEC: replworks/laravel-imageforge

## Purpose

- The product is a Laravel package named `replworks/laravel-imageforge`.
- The product is a client for the Image Forge service and generates Forge URLs from image paths.
- The primary Blade interface is a Utility Class DSL on `<img>` elements.
- `ImageForge::make()` remains the explicit API for PHP, email, API, `srcset`, and other non-HTML use.

Definitions:

- Image Path: a relative file path string, or null.
- Source URL: the original URL returned by Laravel's asset or storage capability.
- Forge URL: a URL pointing to Image Forge.
- Option: one of `w`, `h`, `fit`, `q`, or `f`.
- Builder: an `ImageForge` instance returned by `ImageForge::make()`.
- Utility Class DSL: recognized classes in an `<img>` `class` attribute that configure a Forge URL.

## Goals

- Configure Image Forge once through `config/imageforge.php` and environment variables.
- Let a Developer write `<img src="photo.jpg" class="if-w-480 if-h-320">` without SDK calls in Blade markup.
- Preserve an explicit Builder API for contexts where Blade precompilation does not apply.

## Inputs and configuration

- `ImageForge::make()` accepts `$path`, `$w`, `$h`, `$fit`, `$q`, and `$f` in that order; named arguments are supported.
- `imageforge.base_url` defaults to `https://img.repl.net`.
- `imageforge.service` has no default.
- `imageforge.disk` defaults to null.
- `imageforge.dsl_prefix` defaults to `if-` and is configurable in `config/imageforge.php`.
- `IMAGEFORGE_BASE_URL`, `IMAGEFORGE_SERVICE`, and `IMAGEFORGE_DISK` map to their matching settings.

## Builder behavior

- `ImageForge::make()` returns a Builder and does not build a URL.
- `Builder::asset()` and `Builder::storage()` return null for null or empty paths.
- With w absent, they return the corresponding Source URL unchanged.
- With w present, they return a Forge URL using the existing URL composer.
- Builder option values are passed through without validation, clamping, encoding, or modification.
- The Forge query order is `w`, `h`, `fit`, `q`, `f`; null options are omitted.
- The DSL transforms the resolved `src` value and does not infer whether it came from `asset()` or storage.

## Utility Class DSL

The service provider registers a Blade precompiler. It scans `<img>` tags at Blade compilation time and emits a normal escaped Blade echo for the generated source URL. URL resolution itself occurs when the compiled view renders.

The default prefix is `if-`; every class below uses the configured prefix.

| Class | Result |
| --- | --- |
| `if-w-{n}` | `w=n`, clamped to 0..2000 |
| `if-h-{n}` | `h=n`, clamped to 0..2000 |
| `if-q-{n}` | `q=n`, clamped to 1..100 |
| `if-fit` | `fit=fit` |
| `if-fill`, `if-crop` | `fit=fill` |
| `if-fit-auto` | `fit=auto` |
| `if-webp`, `if-avif`, `if-jpeg`, `if-png` | matching `f` |
| `if-auto` | omit `f` |

Rules:

- Classes are processed in source order; the last recognized value of each option wins.
- If no width class is present but another recognized class is present, the DSL supplies `w=0`.
- Only recognized classes are removed from `class`; unknown prefixed classes remain.
- If no classes remain, the `class` attribute is removed.
- If no recognized DSL class is present, the `<img>` is unchanged.
- `src` may be a static value, one Blade echo, or a safely concatenable mixture of static text and Blade echoes. Unsafe or unsupported mixtures leave the whole tag unchanged.
- The generated `src` always uses `{{ ... }}` HTML escaping.
- Missing or empty `src` resolves to null without raising an error.
- The `src` value should already be a correctly resolved display URL, such as the result of `asset()` or a storage URL helper.
- The scanner supports attribute order, single or double quotes, newlines, and optional self-closing syntax.
- Text inside `@verbatim`, Blade comments, `@php`, `<script>`, and `<style>` is not scanned. `<x-...>` tags are not treated as `<img>` tags.

## Installation and configuration

- `composer require replworks/laravel-imageforge` installs the package.
- Laravel package discovery registers the service provider.
- `php artisan vendor:publish --tag=imageforge-config` publishes `config/imageforge.php`.

## Errors and non-goals

- A Forge URL request with an unset service raises an exception mentioning `imageforge.service`.
- An undefined storage disk error propagates unchanged from Laravel.
- The package does not provide Blade directives or the `HasImageForge` model Trait.
- The package does not validate or clamp values passed directly to `ImageForge::make()`.
- The package does not contact Image Forge itself.

## Acceptance criteria

- Existing Builder, configuration, and URL-composition acceptance criteria remain passing.
- A static `<img src="photo.jpg" class="rounded if-w-480 if-h-320 if-crop">` renders an escaped Forge URL with `class="rounded"`.
- Last values win, `if-crop` maps to fill, and out-of-range DSL numbers clamp to service ranges.
- A recognized option without width emits `w=0`.
- Unknown prefixed classes remain; an empty class attribute is removed.
- Dynamic and mixed safe `src` expressions render correctly; unsafe mixtures remain unchanged.
- Absolute, protocol-relative, and data URLs are unchanged except for recognized class removal.
- Protected regions are unchanged.
- `Dsl::parse()` is a pure mapping function and `Dsl::url()` reuses `ImageForge::make()`.
