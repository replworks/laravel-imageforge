# ARCHITECTURE: replworks/laravel-imageforge

## 1. Purpose

The product has one explicit URL API and one template convenience layer. The convenience layer parses static template structure once, then delegates URL resolution to the existing Builder.

## 2. Components

| ID | Component | Responsibility |
| --- | --- | --- |
| C1 | Builder | Holds the Image Path and direct options; resolves asset or storage URLs when called. |
| C2 | Source URL Resolver | Obtains the Source URL from the selected asset or storage capability. |
| C3 | Forge URL Composer | Builds the Forge URL and query string from a Source URL and direct options. |
| C4 | Settings Access | Reads current settings during resolution. |
| C5 | Settings Definition | Defines settings, defaults, and environment mapping. |
| C6 | DSL Parser | Maps class tokens to DSL options and consumed tokens. It clamps only DSL values. |
| C7 | Template Scanner | Finds eligible image tags, parses attributes safely, and emits escaped URL expressions. |
| C8 | Registration | Activates settings, publishing, and the template scanner. |

External capabilities are supplied by the host: asset URL generation, storage URL generation, settings storage, template compilation, and template rendering. The product never contacts Image Forge.

## 3. Data flow

### Direct flow

`Developer -> C1 -> C2 -> C3 -> URL or null`

C1 captures values without resolving anything. Resolution obtains the Source URL first, returns it unchanged when w is null, and otherwise delegates to C3.

### DSL flow

`template source -> C7 -> C6 -> compiled escaped expression -> C1 -> URL or null`

C7 performs structural scanning and class removal during compilation. C6 parses only literal class tokens. At render time the generated expression calls the runtime DSL helper, which delegates to C1. The DSL supplies `w=0` when a recognized class exists without a width class.

## 4. Template scanner boundaries

- A candidate begins with an `img` element name and ends at the first `>` outside quotes, Blade echo blocks, and parenthesized Blade expressions.
- Attributes may be in any order and may use either quote style, line breaks, or self-closing syntax.
- The scanner parses only literal `class` values. Dynamic class values are left untouched.
- A source value may be static, one Blade echo, or a concatenation of static segments and complete Blade echoes. Unsupported mixtures leave the whole image tag untouched.
- The scanner skips verbatim blocks, Blade comments, PHP blocks, script blocks, and style blocks.
- The scanner never treats a component element as an image element.

## 5. DSL parser ownership

C6 owns all class-to-option decisions:

- width and height clamp to 0..2000;
- quality clamps to 1..100;
- format and fit aliases map to their documented values;
- repeated values use the last class in source order;
- only recognized tokens are consumed.

C6 returns `options` and `consumed`. It performs no URL work and has no template dependency.

## 6. Runtime DSL helper

The runtime helper owns only DSL-specific decisions:

- null or empty source returns null;
- the resolved source URL is transformed regardless of whether it came from asset or storage;
- the parsed options are passed to the existing Builder;
- it never duplicates Source URL resolution or Forge URL composition.

## 7. Invariants

- Direct Builder options are never clamped or otherwise modified.
- DSL clamping does not change direct Builder behavior.
- URL resolution never occurs during template compilation.
- Generated output always uses HTML escaping.
- A tag with no recognized DSL option is byte-for-byte unchanged.
- An unsafe source expression leaves both the source and classes unchanged.
- A recognized token is removed exactly once; an unrecognized prefixed token remains.
- No state is shared between compilations or render calls.
- Settings are read at render-time resolution, not parser or registration time.

## 8. Failure boundaries

- An unset service raises the existing service configuration error when a Forge URL is needed.
- Host errors from asset or storage URL generation propagate unchanged.
- A null or empty DSL source returns null.
- Unsupported template syntax is a no-op, not a parser error.

## 9. Non-goals

- No model field discovery or model Trait.
- No Blade directives.
- No network calls, URL validation, caching, or custom option presets.
- No rewriting of arbitrary HTML, CSS, JavaScript, or component internals.
