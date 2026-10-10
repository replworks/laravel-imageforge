# PRODUCT_SPEC: replworks/laravel-imageforge

## Purpose

- The product is a Laravel package named replworks/laravel-imageforge.
- The product generates Image Forge URLs from image paths stored on Laravel models.
- The product is distributed through Packagist and installed with composer.
- The product is used by Laravel application developers, referred to as Developer.

Definitions used in this document:

- Image Forge: the external image conversion service reachable at the Base URL.
- Image Path: a relative file path string, for example templates/modern-minimalist-tech/image.png.
- Base URL: the origin of the Image Forge service.
- Service Identifier: the name that identifies the Developer's application inside Image Forge.
- Option: one of w, h, fit, q, f.
- Source URL: the original URL of an Image Path, without Image Forge.
- Origin Path: the URL path component of a Source URL.
- Forge URL: a URL that points to Image Forge for an Image Path.
- Builder: an instance of the ImageForge class returned by ImageForge::make().

## Problem

- Without the product, Image Forge URL assembly code is written separately in each model.
- Without the product, Image Forge settings code is written separately in each application.
- Without the product, a change in the Image Forge URL format requires edits in every application and its tests.

## Product Goals

- The Developer generates a Forge URL for a stored Image Path by calling one method.
- The Developer selects between a storage-disk Source URL and an asset() Source URL as the basis of the Forge URL.
- The Developer sets Base URL, Service Identifier, and disk through one config file and environment variables.
- The Developer declares image fields on a model and calls a generated method per field.

## Users

- Developer: a person who builds a Laravel application and uses the Image Forge service.
- The first Developer is the owner of wifinote.net.

## Inputs

- Image Path: a string, or null.
- w: integer, optional.
- h: integer, optional.
- fit: string, optional.
- q: integer, optional.
- f: string, optional.
- Config key imageforge.base_url: string, default https://img.repl.net.
- Config key imageforge.service: string, no default.
- Config key imageforge.disk: string, default null.
- Environment variable IMAGEFORGE_BASE_URL: sets imageforge.base_url.
- Environment variable IMAGEFORGE_SERVICE: sets imageforge.service.
- Environment variable IMAGEFORGE_DISK: sets imageforge.disk.
- Model property `$imageForgeFields`: array of attribute names, declared on a model that uses the Trait.

## Outputs

- Forge URL: a string.
- Source URL: a string.
- null.

Forge URL structure:

- Forge URL = Base URL + "/" + Service Identifier + Origin Path + "?" + query string.
- The query string contains the supplied Options as name=value pairs joined with "&".
- The query string order is w, h, fit, q, f.
- An Option whose value is null MUST NOT appear in the query string.

## Constraints

- The product MUST require Laravel 12.0 or later and earlier than 13.0.
- The product MUST require PHP 8.3 or later.
- The product MUST NOT validate Option values.

## External Systems

- Image Forge (img.repl.net): the product relies on the following behavior.
  - A request to Forge URL returns a converted image.
  - w is an integer from 0 to 2000. The value 0 means automatic.
  - h is an integer from 0 to 2000. The value 0 means automatic.
  - fit is one of fit, fill, auto. Any other value is treated as fit.
  - q is an integer from 1 to 100. The default is 80. An absent value or 0 is treated as 80.
  - f is one of avif, webp, jpeg, png. When f is absent, the format follows the request Accept header.
  - The Service Identifier is registered in Image Forge by the Developer.
- Laravel framework: the product relies on the following behavior.
  - `Storage::disk($disk)->url($path)` returns the Source URL of a stored file.
  - `asset($path)` returns the Source URL of a public asset.
  - The service container reads config values.
  - Package auto-discovery registers the product service provider.
  - vendor:publish copies tagged files.
- Packagist and composer: the product relies on composer require installing the package.

## User Interface

The product has no graphical screens. The following surfaces are the interfaces the Developer uses.

### SCR-001

- ID: SCR-001
- Purpose: Create a Builder and obtain URLs.
- Elements:
  - EL-001: `ImageForge::make($path, $w, $h, $fit, $q, $f)`. Purpose: create a Builder. User actions: call with an Image Path or null and optional Options in this positional order. Named arguments w, h, fit, q, f are supported.
  - EL-002: `Builder::storage()`. Purpose: return a URL based on the storage-disk Source URL. User actions: call on a Builder.
  - EL-003: `Builder::asset()`. Purpose: return a URL based on the asset() Source URL. User actions: call on a Builder.
- States:
  - Image Path empty: Image Path is null or an empty string.
  - Width absent: w is null.
  - Width present: w is an integer.
  - Service Identifier unset: imageforge.service is null or an empty string.
  - Service Identifier set: imageforge.service is a non-empty string.
- Navigation:
  - EL-001 call -> a Builder is returned.
  - EL-002 call -> a string or null is returned.
  - EL-003 call -> a string or null is returned.
- Interactions:
  - EL-001 call with any arguments -> a Builder is returned and no URL is built.
  - EL-002 call, Image Path empty -> null.
  - EL-002 call, Width absent -> Source URL from `Storage::disk($disk)->url($path)`.
  - EL-002 call, Width present, Service Identifier set -> Forge URL whose Origin Path is the Origin Path of `Storage::disk($disk)->url($path)`.
  - EL-003 call, Image Path empty -> null.
  - EL-003 call, Width absent -> Source URL from `asset($path)`.
  - EL-003 call, Width present, Service Identifier set -> Forge URL whose Origin Path is the Origin Path of `asset($path)`.
  - EL-002 or EL-003 call, Width present, Service Identifier unset -> ERR-001.

### SCR-002

- ID: SCR-002
- Purpose: Generate one Builder-returning method per declared image field on a model.
- Elements:
  - EL-004: Trait `Replworks\ImageForge\Concerns\HasImageForge`. Purpose: add generated methods to a model. User actions: add `use HasImageForge;` to a model.
  - EL-005: Property `$imageForgeFields`. Purpose: list the attribute names that receive generated methods. User actions: declare as `protected array $imageForgeFields` on the model.
  - EL-006: Generated method `{camelCaseFieldName}ImageForge($w, $h, $fit, $q, $f)`. Purpose: create a Builder for the attribute value. User actions: call with optional Options in this positional order. Named arguments w, h, fit, q, f are supported.
- States:
  - Field declared: the attribute name is in `$imageForgeFields`.
  - Field undeclared: the attribute name is not in `$imageForgeFields`.
- Navigation:
  - EL-006 call -> a Builder is returned, then SCR-001 EL-002 or EL-003 is called.
- Interactions:
  - EL-006 call, Field declared -> a Builder created from the attribute value and the supplied Options.
  - EL-006 call, Field undeclared -> ERR-002.
  - The generated method name for attribute preview_path -> previewPathImageForge.
  - The generated method name for attribute thumbnail_path -> thumbnailPathImageForge.

### SCR-003

- ID: SCR-003
- Purpose: Configure the product.
- Elements:
  - EL-007: File config/imageforge.php. Purpose: hold imageforge.base_url, imageforge.service, imageforge.disk. User actions: edit values.
  - EL-008: Environment variables IMAGEFORGE_BASE_URL, IMAGEFORGE_SERVICE, IMAGEFORGE_DISK. Purpose: set the config values. User actions: define in the application environment.
- States:
  - Config file not published: the config values use the product defaults and environment variables.
  - Config file published: the config values come from config/imageforge.php.
  - imageforge.disk null: the application default filesystem disk is used.
  - imageforge.disk set: the named disk is used.
- Navigation:
  - SCR-004 EL-010 -> EL-007 exists in the application.
- Interactions:
  - Set IMAGEFORGE_BASE_URL -> imageforge.base_url takes that value.
  - Set IMAGEFORGE_SERVICE -> imageforge.service takes that value.
  - Set IMAGEFORGE_DISK -> imageforge.disk takes that value.

### SCR-004

- ID: SCR-004
- Purpose: Install and publish the config.
- Elements:
  - EL-009: Command `composer require replworks/laravel-imageforge`. Purpose: install the package. User actions: run in the application root.
  - EL-010: Command `php artisan vendor:publish --tag=imageforge-config`. Purpose: copy config/imageforge.php into the application. User actions: run in the application root.
- States:
  - Not installed: the package is absent.
  - Installed: the package is present.
- Navigation:
  - EL-009 complete -> SCR-001 and SCR-002 are available.
  - EL-010 complete -> SCR-003 EL-007 exists.
- Interactions:
  - EL-009 -> the service provider is registered by auto-discovery without manual registration.
  - EL-010 -> config/imageforge.php is created in the application.

## Functional Requirements

- FR-001: `ImageForge::make()` MUST return an ImageForge instance.
- FR-002: `ImageForge::make()` MUST accept `$path`, `$w`, `$h`, `$fit`, `$q`, `$f` in this positional order.
- FR-003: `ImageForge::make()` MUST accept null for `$path`.
- FR-004: `ImageForge::make()` MUST NOT build a URL.
- FR-005: `Builder::storage()` MUST return null when the Image Path is null.
- FR-006: `Builder::storage()` MUST return null when the Image Path is an empty string.
- FR-007: `Builder::storage()` MUST return the Source URL from `Storage::disk($disk)->url($path)` when w is null.
- FR-008: `Builder::storage()` MUST return a Forge URL when w is not null.
- FR-009: `Builder::asset()` MUST return null when the Image Path is null.
- FR-010: `Builder::asset()` MUST return null when the Image Path is an empty string.
- FR-011: `Builder::asset()` MUST return the Source URL from `asset($path)` when w is null.
- FR-012: `Builder::asset()` MUST return a Forge URL when w is not null.
- FR-013: A Forge URL MUST begin with imageforge.base_url.
- FR-014: A Forge URL MUST contain imageforge.service as the first path segment after imageforge.base_url.
- FR-015: A Forge URL MUST contain the Origin Path of the Source URL after the Service Identifier segment.
- FR-016: A Forge URL query string MUST list the Options in the order w, h, fit, q, f.
- FR-017: A Forge URL query string MUST NOT contain an Option whose value is null.
- FR-018: A Forge URL query string MUST contain w=0 when w is 0.
- FR-019: The product MUST pass Option values to the query string without validation or modification.
- FR-020: `Builder::storage()` MUST use the disk named in imageforge.disk when imageforge.disk is not null.
- FR-021: `Builder::storage()` MUST use the application default filesystem disk when imageforge.disk is null.
- FR-022: imageforge.base_url MUST default to https://img.repl.net.
- FR-023: imageforge.service MUST have no default value.
- FR-024: imageforge.disk MUST default to null.
- FR-025: The config values MUST be available without publishing config/imageforge.php.
- FR-026: IMAGEFORGE_BASE_URL MUST set imageforge.base_url.
- FR-027: IMAGEFORGE_SERVICE MUST set imageforge.service.
- FR-028: IMAGEFORGE_DISK MUST set imageforge.disk.
- FR-029: The command in EL-010 MUST create config/imageforge.php in the application.
- FR-030: The product service provider MUST be registered by Laravel package auto-discovery.
- FR-031: The Trait HasImageForge MUST be available at `Replworks\ImageForge\Concerns\HasImageForge`.
- FR-032: The class ImageForge MUST be available at `Replworks\ImageForge\ImageForge`.
- FR-033: The Trait MUST generate a method named `{camelCaseFieldName}ImageForge` for each attribute name in `$imageForgeFields`.
- FR-034: A generated method MUST return an ImageForge instance created from the attribute value and the supplied Options.
- FR-035: A generated method MUST accept `$w`, `$h`, `$fit`, `$q`, `$f` in this positional order.
- FR-036: A generated method MUST accept the named arguments w, h, fit, q, f.
- FR-037: The Trait MUST NOT generate a method for an attribute name that is not in `$imageForgeFields`.
- FR-038: The product MUST NOT provide a default-field method named imageForge on the Trait.
- FR-039: The product MUST NOT require Eloquent casts.
- FR-040: A Developer MUST be able to use `ImageForge::make()` without the Trait.
- FR-041: The README first line MUST state that the package is a client for the Image Forge service.

## User Flows

- Install flow:
  - Developer runs EL-009.
  - The package is installed.
  - SCR-001 and SCR-002 are available.
- Configure flow:
  - Developer defines IMAGEFORGE_SERVICE.
  - Developer optionally defines IMAGEFORGE_BASE_URL and IMAGEFORGE_DISK.
  - Developer optionally runs EL-010 and edits EL-007.
- Direct URL flow:
  - Developer calls `ImageForge::make()` with an Image Path and w.
  - Developer calls EL-002 or EL-003 on the result.
  - A Forge URL string is returned.
- Trait flow:
  - Developer adds EL-004 to a model.
  - Developer declares EL-005 with attribute names.
  - Developer calls EL-006 with Options.
  - Developer calls EL-002 or EL-003 on the result.
  - A Forge URL string is returned.
- Original URL flow:
  - Developer calls EL-001 or EL-006 without w.
  - Developer calls EL-002 or EL-003.
  - A Source URL string is returned.

## Error Conditions

- ERR-001: Condition: a Forge URL is requested and imageforge.service is null or an empty string. Behavior: the product MUST throw an exception whose message contains imageforge.service.
- ERR-002: Condition: a method named `{name}ImageForge` is called on a model for a name that is not in `$imageForgeFields`. Behavior: the product MUST NOT handle the call, and Laravel MUST throw BadMethodCallException.
- ERR-003: Condition: imageforge.disk names a disk that is not defined in the application. Behavior: the product MUST propagate the exception thrown by Laravel.

## Non-Goals

- The product MUST NOT provide a default-field method named imageForge.
- The product MUST NOT provide Eloquent cast classes.
- The product MUST NOT provide `ImageForge::url()`.
- The product MUST NOT provide a source: argument.
- The product MUST NOT validate Option values.
- The product MUST NOT provide a way to register a Service Identifier or origins in Image Forge.

## Acceptance Criteria

Test fixture: imageforge.service is wifinote, imageforge.base_url is https://img.repl.net, the disk URL is https://wifinote.test/storage, and asset('templates/preview.png') returns https://wifinote.test/templates/preview.png.

- AC-001: `ImageForge::make('templates/preview.png', 320)->storage()` returns https://img.repl.net/wifinote/storage/templates/preview.png?w=320. Verifies FR-008, FR-013, FR-014, FR-015, SCR-001.
- AC-002: `ImageForge::make('templates/preview.png', 320)->asset()` returns https://img.repl.net/wifinote/templates/preview.png?w=320. Verifies FR-012, SCR-001.
- AC-003: `ImageForge::make('templates/preview.png', 320, 240, 'fill', 80, 'webp')->storage()` returns a URL ending with ?w=320&h=240&fit=fill&q=80&f=webp. Verifies FR-016, SCR-001.
- AC-004: `ImageForge::make('templates/preview.png', 320, q: 80)->storage()` returns a URL ending with ?w=320&q=80. Verifies FR-017, SCR-001.
- AC-005: `ImageForge::make('templates/preview.png')->storage()` returns https://wifinote.test/storage/templates/preview.png. Verifies FR-007, SCR-001.
- AC-006: `ImageForge::make('templates/preview.png')->asset()` returns https://wifinote.test/templates/preview.png. Verifies FR-011, SCR-001.
- AC-007: `ImageForge::make('templates/preview.png', 0)->storage()` returns a URL ending with ?w=0. Verifies FR-018, SCR-001.
- AC-008: `ImageForge::make(null, 320)->storage()` returns null. Verifies FR-005, SCR-001.
- AC-009: `ImageForge::make('', 320)->asset()` returns null. Verifies FR-010, SCR-001.
- AC-010: `ImageForge::make('templates/preview.png', 320, fit: 'invalid')->storage()` returns a URL containing fit=invalid. Verifies FR-019, SCR-001.
- AC-011: With imageforge.service unset, `ImageForge::make('templates/preview.png', 320)->storage()` throws an exception whose message contains imageforge.service. Verifies ERR-001, FR-023, SCR-001.
- AC-012: With imageforge.service unset, `ImageForge::make('templates/preview.png')->storage()` returns the Source URL without throwing. Verifies FR-007, SCR-001.
- AC-013: With IMAGEFORGE_BASE_URL set to https://img.example.test, a Forge URL begins with https://img.example.test/wifinote/. Verifies FR-013, FR-026, SCR-003.
- AC-014: With IMAGEFORGE_DISK set to a defined disk, `Builder::storage()` uses the URL of that disk. Verifies FR-020, FR-028, SCR-003.
- AC-015: With imageforge.disk null, `Builder::storage()` uses the URL of the application default disk. Verifies FR-021, SCR-003.
- AC-016: With imageforge.disk set to an undefined disk name, `Builder::storage()` propagates the Laravel exception. Verifies ERR-003, SCR-001.
- AC-017: A model that uses HasImageForge and declares `$imageForgeFields = ['preview_path']` returns an ImageForge instance from `previewPathImageForge(320)`. Verifies FR-033, FR-034, SCR-002.
- AC-018: `previewPathImageForge(320, h: 240)->storage()` on that model with preview_path set to templates/preview.png returns a URL ending with ?w=320&h=240. Verifies FR-035, FR-036, SCR-002.
- AC-019: A model that declares `$imageForgeFields = ['preview_path', 'thumbnail_path']` returns an ImageForge instance from `thumbnailPathImageForge(150)`. Verifies FR-033, SCR-002.
- AC-020: Calling `coverPathImageForge(320)` on a model that does not declare cover_path throws BadMethodCallException. Verifies ERR-002, FR-037, SCR-002.
- AC-021: Calling `imageForge(320)` on a model that uses HasImageForge throws BadMethodCallException. Verifies FR-038, SCR-002.
- AC-022: A model that does not use casts for its image attribute returns the stored string when the attribute is read. Verifies FR-039, SCR-002.
- AC-023: After EL-010 runs, config/imageforge.php exists in the application. Verifies FR-029, SCR-004.
- AC-024: Without running EL-010, `config('imageforge.base_url')` returns https://img.repl.net. Verifies FR-022, FR-025, SCR-003.
- AC-025: After EL-009 runs, no manual service provider registration is needed and `ImageForge::make()` is callable. Verifies FR-030, FR-032, SCR-004.
- AC-026: composer installation of the package fails on Laravel 11.x and on Laravel 13.x. Verifies the Laravel requirement in Constraints.
- AC-027: The first line of README.md states that the package is a client for the Image Forge service. Verifies FR-041.
- AC-028: composer installation of the package fails on PHP 8.2. Verifies the PHP requirement in Constraints.
