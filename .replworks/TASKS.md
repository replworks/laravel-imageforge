# TASKS

Execution checklist in dependency order. Each task is completed by one AI in one session, then work stops (AGENTS.md).
References name PRODUCT_SPEC.md and TECH_STACK.md sections, ARCHITECTURE.md sections, and component IDs (C1-C8).
Example values use service `wifinote` and application URL `https://wifinote.test`.
"Unit tests passing" means the unit tests written for that task pass.

- [X] T-001 Project foundation
  - Satisfies: TECH_STACK.md Section 1 (PHP and Laravel version constraints)
  - External boundary: no
  - Acceptance criteria:
    - Every constraint in TECH_STACK.md is satisfied.
    - Every check defined in TECH_STACK.md passes.
    - composer installation of the package fails on PHP versions and Laravel versions outside the TECH_STACK.md constraints.

- [X] T-002 Developer configures the product through environment variables and an optional published config
  - Satisfies: PRODUCT_SPEC.md Inputs and configuration; Installation and configuration
  - External boundary: no
  - Acceptance criteria:
    - After `php artisan vendor:publish --tag=imageforge-config` runs, `config/imageforge.php` exists in the application.
    - Without any publishing step, `config('imageforge.base_url')` returns `https://img.repl.net`, the service setting has no value, and the disk setting is null.
    - Defining each of `IMAGEFORGE_BASE_URL`, `IMAGEFORGE_SERVICE`, and `IMAGEFORGE_DISK` sets its corresponding setting.
    - The product's settings are available in an application that has not registered anything manually (package discovery).
    - Environment values are read only by the Settings Definition (ARCHITECTURE.md C5).
    - Unit tests passing.

- [X] T-003 Developer obtains a Source URL, or null, from a Builder
  - Satisfies: PRODUCT_SPEC.md Inputs and configuration; Builder behavior; Installation and configuration
  - External boundary: no
  - Acceptance criteria:
    - `ImageForge::make('templates/preview.png')->storage()` returns `https://wifinote.test/storage/templates/preview.png`.
    - `ImageForge::make('templates/preview.png')->asset()` returns `https://wifinote.test/templates/preview.png`.
    - `ImageForge::make(null, 320)->storage()` and `ImageForge::make('', 320)->asset()` return null.
    - With the service setting unset, `ImageForge::make('templates/preview.png')->storage()` returns the Source URL without throwing.
    - With w absent, `storage()` uses the disk named by the disk setting when it is set, and the application default disk when it is null.
    - With w absent and the disk setting naming an undefined disk, `storage()` propagates the Laravel error unchanged (PRODUCT_SPEC.md Errors and non-goals).
    - After `composer require`, no manual service provider registration is needed and `ImageForge::make()` is callable.
    - Creating a Builder with an undefined disk setting and an unset service setting raises no error (`ImageForge::make()` does not build a URL).
    - A null or empty Image Path returns null from both resolution calls without reading any setting.
    - With w absent, h, fit, q and f supplied together still return the Source URL unchanged.
    - A Builder works without any model integration.
    - Unit tests passing.

- [X] T-004 Developer obtains a Forge URL from a storage-based Builder
  - Satisfies: PRODUCT_SPEC.md Builder behavior; Errors and non-goals
  - External boundary: no
  - Acceptance criteria:
    - `ImageForge::make('templates/preview.png', 320)->storage()` returns `https://img.repl.net/wifinote/storage/templates/preview.png?w=320`.
    - `ImageForge::make('templates/preview.png', 0)->storage()` returns a URL ending with `?w=0`.
    - With the service setting unset, `ImageForge::make('templates/preview.png', 320)->storage()` throws an exception whose message contains `imageforge.service`; the error is raised by the Forge URL Composer (C3) and no Source URL or null is returned instead (ARCHITECTURE.md Section 8).
    - With `IMAGEFORGE_BASE_URL` set to `https://img.example.test`, a Forge URL begins with `https://img.example.test/wifinote/`.
    - With w present and the disk setting naming an undefined disk, the Laravel error propagates unchanged (ARCHITECTURE.md Section 8).
    - The Forge URL equals base URL + "/" + service + Origin Path + "?" + query with nothing added, removed or altered.
    - The Source URL's own query and fragment do not appear in the Forge URL.
    - Changing a setting between two calls on the same Builder changes the second result (ARCHITECTURE.md Section 7: settings are read at resolution; no shared state).
    - Unit tests passing.

- [X] T-005 Developer controls the Forge URL query with h, fit, q and f
  - Satisfies: PRODUCT_SPEC.md Builder behavior
  - External boundary: no
  - Acceptance criteria:
    - `ImageForge::make('templates/preview.png', 320, 240, 'fill', 80, 'webp')->storage()` returns a URL ending with `?w=320&h=240&fit=fill&q=80&f=webp`.
    - `ImageForge::make('templates/preview.png', 320, q: 80)->storage()` returns a URL ending with `?w=320&q=80`.
    - `ImageForge::make('templates/preview.png', 320, fit: 'invalid')->storage()` returns a URL containing `fit=invalid`.
    - The query lists only non-null options, in the order w, h, fit, q, f.
    - An option value containing characters reserved in URLs appears in the query exactly as supplied.
    - Option values the Image Forge service treats specially (out of range, unknown fit or f) produce no error in the product (ARCHITECTURE.md Section 8).
    - Unit tests passing.

- [X] T-006 Developer obtains a Forge URL from an asset-based Builder
  - Satisfies: PRODUCT_SPEC.md Builder behavior; Errors and non-goals
  - External boundary: no
  - Acceptance criteria:
    - `ImageForge::make('templates/preview.png', 320)->asset()` returns `https://img.repl.net/wifinote/templates/preview.png?w=320`.
    - With the service setting unset and w present, the call raises the exception mentioning `imageforge.service` (ARCHITECTURE.md Section 8).
    - With the disk setting naming an undefined disk, the asset-based call still returns its result (the disk setting is not used by this strategy; ARCHITECTURE.md C2).
    - The result's Origin Path comes from the asset-based Source URL, not the storage-based one.
    - Unit tests passing.

- [X] T-007 Model Trait integration (superseded before first release)
  - The pre-release product scope no longer includes model field binding or the HasImageForge Trait.

- [X] T-008 Undeclared model field handling (superseded before first release)
  - The pre-release product scope no longer includes model field binding.

- [X] T-009 README first-line slot states the package is a client for the Image Forge service
  - Satisfies: PRODUCT_SPEC.md Purpose
  - External boundary: no
  - Acceptance criteria:
    - The first line of README.md states that the package is a client for the Image Forge service.
    - The wording beyond that statement is not part of this task (human-owned, AGENTS.md HUMAN_OWNED_CHANGES).

- [X] T-010 Blade directive integration (superseded before first release)
  - The pre-release product scope uses the Utility Class DSL instead of custom directives.

- [X] T-011 Raw Blade directive integration (superseded before first release)
  - The pre-release product scope uses escaped DSL output and provides no raw directives.

- [X] T-012 Developer configures Image Forge URLs with Blade Utility Classes
  - Satisfies: PRODUCT_SPEC.md Utility Class DSL and Acceptance criteria.
  - External boundary: no
  - Acceptance criteria:
    - `Dsl::parse()` maps recognized classes, clamps numeric values, honors source order, and preserves unknown prefixed classes.
    - The service provider registers a compile-time template scanner.
    - Eligible static and dynamic `<img>` sources render escaped Forge URLs through `Dsl::url()` and the existing Builder.
    - Storage selection, default width, absolute URL passthrough, protected regions, and unsupported source expressions follow PRODUCT_SPEC.md.
    - Unit tests passing.

- [X] T-013 Package supports Laravel 13 alongside Laravel 12
  - Satisfies: TECH_STACK.md Sections 1 and 6 (Laravel and PHP versions, CI matrix)
  - External boundary: no
  - Acceptance criteria:
    - `composer.json` allows `illuminate/*` `^12.0|^13.0` and `orchestra/testbench` `^10.0|^11.0`.
    - CI runs PHPUnit on Laravel `12.*` with Testbench `10.*` and Laravel `13.*` with Testbench `11.*`.
    - Unit tests passing on both Laravel versions.

- [X] T-014 Package supports PHP 8.4 alongside PHP 8.3
  - Satisfies: TECH_STACK.md Sections 1 and 6 (Laravel and PHP versions, CI matrix)
  - External boundary: no
  - Acceptance criteria:
    - `composer.json` PHP constraint allows PHP `8.3` and `8.4`.
    - CI runs PHPUnit on PHP `8.3`/`8.4` with Laravel `12.*` and PHP `8.4` with Laravel `13.*`.
    - Unit tests passing on PHP `8.3` and `8.4`.
