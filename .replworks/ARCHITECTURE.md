# ARCHITECTURE: replworks/laravel-imageforge

Normative language: MUST, MUST NOT, SHOULD follow RFC 2119 meaning.
This document defines internal structure only. Product behavior is defined in PRODUCT_SPEC.md and is referenced here by ID (FR-xxx, ERR-xxx, AC-xxx, SCR/EL-xxx). If this document and PRODUCT_SPEC.md disagree on externally visible behavior, PRODUCT_SPEC.md wins and this document MUST be corrected.

---

## 1. Purpose

- Define the internal parts of the product, what each part owns, and what each part must never do.
- Define how information moves from a Developer call to a returned URL (or null, or an error).
- Define the invariants that every implementation MUST preserve.
- Remove ambiguity so that each behavior has exactly one owning component.

---

## 2. Core Concepts

Terms from PRODUCT_SPEC.md (Image Path, Base URL, Service Identifier, Option, Source URL, Origin Path, Forge URL, Builder) keep their meaning. Additional architectural terms:

- **Image Reference**: the immutable pair (Image Path, Option set) held by a Builder. It contains no URL.
- **Option set**: the five values w, h, fit, q, f. Each is either supplied or null. No value is ever defaulted, coerced, or validated by this product.
- **Resolution**: the act of turning an Image Reference into a result (URL string or null) when `storage()` or `asset()` is called. Resolution is the only time URLs are built and the only time settings are read.
- **Strategy**: the choice of Source URL origin for a Resolution. Exactly two exist: `storage` and `asset`. The Strategy is chosen by the Developer per call, never by the product.
- **Passthrough result**: a Source URL returned unchanged because w is null.
- **Settings**: the three values base_url, service, disk (PRODUCT_SPEC config keys `imageforge.*`).
- **Host**: the application environment the product runs inside. The Host provides: a disk URL capability, an asset URL capability, a settings store, model attribute storage, and default handling of undefined model method calls. The Host is external to this product.
- **Field Binding**: the mechanism that exposes one Builder-returning method per declared model field.

---

## 3. System Flow

### 3.1 Direct flow

```
Developer
  -> Entry (make) with Image Path + Options
  -> [Builder created, nothing resolved]
  -> Developer calls storage() or asset()
  -> Resolution (section 6.1)
  -> returns: null | Source URL | Forge URL   (or raises an error)
```

### 3.2 Model flow

```
Developer calls {camelCaseField}ImageForge(Options) on a model
  -> Field Binding decides: declared field?
       no  -> call handed back to the Host's default handling, unchanged (ERR-002)
       yes -> raw stored value read; type checked (string or null)
              wrong type -> error (section 8)
              ok -> Entry (make) with value + Options
  -> [Builder created, nothing resolved]
  -> Developer calls storage() or asset()
  -> Resolution (same as 3.1)
```

### 3.3 Settings flow

```
Environment values --(read once, inside Settings Definition only)--> Settings (with defaults)
Resolution --(Settings Access, at call time)--> current Settings value
```

### 3.4 Registration flow

```
Package present in application
  -> Registration makes Settings Definition active (defaults + environment mapping) without any publishing step
  -> Registration offers an optional publish action that copies the Settings Definition into the application
```

---

## 4. Components

| ID  | Component                     | Kind              | Traces to                                               |
| --- | ----------------------------- | ----------------- | ------------------------------------------------------- |
| C1  | Builder (ImageForge)          | Product component | EL-001, EL-002, EL-003, FR-001..FR-012, FR-040, FR-032  |
| C2  | Source URL Resolver           | Product component | FR-007, FR-011, FR-020, FR-021, ERR-003                 |
| C3  | Forge URL Composer            | Product component | FR-008, FR-012..FR-019, ERR-001                         |
| C4  | Settings Access               | Product component | FR-013, FR-014, FR-020, FR-021                          |
| C5  | Settings Definition           | Product component | FR-022..FR-029, EL-007, EL-008                          |
| C6  | Field Binding (HasImageForge) | Product component | EL-004, EL-005, EL-006, FR-031, FR-033..FR-039, ERR-002 |
| C7  | Registration                  | Product component | FR-029, FR-030, FR-042..FR-044, EL-009..EL-012          |

External capabilities (NOT components of this product; the product consumes them and never reimplements them):

| ID  | Capability                                                                                  | Provided by        | Used by                              |
| --- | ------------------------------------------------------------------------------------------- | ------------------ | ------------------------------------ |
| X1  | Disk URL capability: given an optional disk name and a path, returns a Source URL           | Host               | C2                                   |
| X2  | Asset URL capability: given a path, returns a Source URL                                    | Host               | C2                                   |
| X3  | Settings store: holds Settings values under the keys defined by C5                          | Host               | C4, C5                               |
| X4  | Model raw attribute read: returns the stored value of an attribute with no accessor applied | Host               | C6                                   |
| X5  | Default undefined-call handling for models                                                  | Host               | C6                                   |
| X6  | Image Forge service                                                                         | Developer-operated | none (the product never contacts it) |

Dependency direction (a component may call only those listed):

- C1 -> C2, C3
- C2 -> C4, X1, X2
- C3 -> C4
- C4 -> X3
- C5 -> X3 (definition only)
- C6 -> C1 (entry only), X4, X5
- C7 -> C5, C1

No other dependency is allowed. In particular: C2 and C3 never call each other. C1 never reads Settings. C6 never calls C2, C3, or C4.

---

## 5. Component Responsibilities

### C1 Builder

**Responsibilities**

- Capture an Image Reference at creation: the Image Path (as given) and the five Option values (as given).
- Perform no URL work at creation (FR-004).
- On `storage()` / `asset()`, orchestrate the Resolution sequence (section 6.1) for the chosen Strategy.
- Decide the result category: null (empty path), Passthrough result (w is null), or Forge URL (w is not null).

**Inputs**

- Image Path: string or null (as given by caller).
- w, h, q: integer or null. fit, f: string or null.
- A Strategy selection (by which method is called).

**Outputs**

- A Builder instance (at creation).
- At Resolution: string or null, or a propagated error.

**Owns**

- The order of steps in Resolution.
- The decision "empty path -> null" (FR-005, FR-006, FR-009, FR-010). Empty means null or the empty string, nothing else.
- The decision "w is null -> Passthrough result". "w is null" means exactly the null value; w = 0 is present.
- Immutability of the Image Reference.

**Does NOT own**

- Reading Settings (C4).
- Obtaining a Source URL (C2).
- Building a Forge URL, extracting the Origin Path, or rendering the query string (C3).
- Type checking of the Image Path from models (C6).
- Validation or coercion of any Option value (nobody owns this; it is not done).

### C2 Source URL Resolver

**Responsibilities**

- For Strategy `storage`: obtain the Source URL from X1 for the given Image Path, using the disk named in Settings, or the Host's default disk when the disk setting is null.
- For Strategy `asset`: obtain the Source URL from X2 for the given Image Path.
- Return the Source URL exactly as the Host capability returned it.
- Let every error raised by X1/X2 propagate unchanged (ERR-003).

**Inputs**

- Image Path (non-empty string; emptiness is already handled by C1).
- Strategy.
- Disk setting value (obtained from C4 only for Strategy `storage`).

**Outputs**

- Source URL (string), or a propagated Host error.

**Owns**

- The mapping Strategy -> Host capability.
- Disk selection: the disk setting is null -> no disk named; otherwise the value is passed as the disk name with no modification.

**Does NOT own**

- Deciding whether a Source URL or a Forge URL is returned (C1).
- Reading any Setting other than disk.
- Parsing, trimming, or altering the Source URL.
- Catching, translating, or retrying Host errors.

### C3 Forge URL Composer

**Responsibilities**

- Given a Source URL and the Option set, produce the Forge URL.
- Read service and base_url (through C4) and enforce that service is set: null or empty string -> raise ERR-001 with a message containing `imageforge.service`.
- Extract the Origin Path from the Source URL (path component only; the Source URL's own query and fragment are discarded).
- Render the query string: Options in the order w, h, fit, q, f; omit every null Option; each present Option as `name=value`; joined with `&`; values rendered raw (no encoding, no modification).
- Assemble: base_url + "/" + service + Origin Path + "?" + query string, by literal concatenation.

**Inputs**

- Source URL (string).
- Option set (w guaranteed non-null by C1).
- base_url, service (from C4).

**Outputs**

- Forge URL (string), or ERR-001.

**Owns**

- The Forge URL format.
- Query string rendering, including ordering and null omission (FR-016, FR-017, FR-018, FR-019).
- Origin Path extraction.
- The service-unset check.

**Does NOT own**

- Choosing the Source URL or the disk (C2).
- Deciding whether a Forge URL is needed at all (C1).
- Normalizing slashes, trailing slashes, case, or encoding in base_url, service, or Origin Path.
- Verifying that the service exists or is registered in Image Forge.

### C4 Settings Access

**Responsibilities**

- Return the current value of one named Setting (base_url, service, disk) from X3 at the moment of the call.

**Inputs**

- Setting name.

**Outputs**

- The stored value, unchanged (string or null).

**Owns**

- The single read path to Settings for C2 and C3.
- Guarantee of freshness: no caching, no memoization.

**Does NOT own**

- Default values (C5).
- Interpreting values (for example "service is unset" is decided by C3; "disk is null" is decided by C2).
- Reading environment values directly.

### C5 Settings Definition

**Responsibilities**

- Declare the three Settings and their defaults: base_url defaults to `https://img.repl.net`; service has no default; disk defaults to null.
- Map environment values to Settings: IMAGEFORGE_BASE_URL -> base_url, IMAGEFORGE_SERVICE -> service, IMAGEFORGE_DISK -> disk.
- Be the only place in the product where environment values are read.
- Be copyable into the application so the Developer can edit it (EL-007).

**Inputs**

- Environment values (optional).

**Outputs**

- Settings values in X3.

**Owns**

- Setting names, defaults, and environment mapping.

**Does NOT own**

- Reading Settings during Resolution (C4).
- Making itself active (C7).
- Any secret. The product defines, reads, and stores no secrets.

### C6 Field Binding

**Responsibilities**

- Let a model declare a list of attribute names (EL-005).
- For a call on the model whose method name is not otherwise defined by the model, decide whether the name is `{camelCaseFieldName}ImageForge` for a declared field (matching rules in section 6.2).
- Match: read the raw stored attribute value via X4; require it to be a string or null; create a Builder via C1 entry with that value and the supplied Options (positional order w, h, fit, q, f; named arguments w, h, fit, q, f).
- No match: hand the call back to X5 with the original name and arguments, unchanged. The product raises nothing itself in this case (ERR-002).
- Raise an error when a matched field's raw value is neither string nor null (section 8, F-04).

**Inputs**

- Method name and arguments of an otherwise-undefined model call.
- The model's declared field list.
- The model's raw attribute values (via X4).

**Outputs**

- A Builder (match), or the result/error of X5 (no match), or the type error (F-04).

**Owns**

- The mapping from method name to declared field.
- Reading the raw stored value.
- The string-or-null check of that value.
- Absence of any default-field method (nothing is matched without a field name).

**Does NOT own**

- Anything about URLs, Strategies, or Settings.
- Defining methods the model already defines (the model's own methods always win because C6 only sees undefined names).
- Handling of unmatched names beyond handing them to X5.
- Eloquent-style type conversion of attributes (the raw value is used; accessors and casts are bypassed).

### C7 Registration

**Responsibilities**

- Make C5 active in the application automatically on installation, with no manual registration step (FR-030, FR-025).
- Offer the publish action that copies C5 into the application (FR-029).
- Register the `imageForgeAsset` and `imageForgeStorage` Blade directives (FR-042..FR-044).

**Inputs**

- The Host's package discovery and publishing mechanisms.

**Outputs**

- Active Settings Definition; optional published copy.

**Owns**

- Wiring only.

**Does NOT own**

- Any logic about URLs, fields, or Resolution.
- Reading Settings values.

---

## 6. Responsibility Boundaries

### 6.1 Resolution sequence (owner: C1)

Executed identically for `storage()` and `asset()`; only the Strategy differs. Steps run in this exact order; a step that returns or raises ends the Resolution.

1. Image Path is null or empty string -> return null. (No Settings read. No Host capability call.)
2. Obtain Source URL via C2 with the Strategy. (Host errors propagate, including undefined disk.)
3. w is null -> return the Source URL unchanged (Passthrough result). (service and base_url are never read.)
4. Compose the Forge URL via C3. (service unset -> ERR-001.)
5. Return the Forge URL.

### 6.2 Field Binding name matching (owner: C6)

- A call is a candidate only if the method name ends with the exact, case-sensitive suffix `ImageForge` and has a non-empty prefix before it.
- The prefix is compared, exactly and case-sensitively, to the camelCase form of each declared field name.
- camelCase form: split the field name on `_`; keep the first segment unchanged; capitalize the first character of each later segment; concatenate. (`preview_path` -> `previewPath`; `image` -> `image`.)
- A prefix equal to the camelCase form of a declared field is a match. Anything else is not a match.
- The method name `imageForge` (lowercase i) has no matching suffix and is therefore never a match (FR-038, AC-021).

### 6.3 Ownership matrix (one owner per decision)

| Decision                                      | Owner              |
| --------------------------------------------- | ------------------ |
| Order of Resolution steps                     | C1                 |
| Empty path -> null                            | C1                 |
| w null -> Source URL                          | C1                 |
| Which Host capability supplies the Source URL | C2                 |
| Which disk is used                            | C2 (value from C4) |
| Forge URL shape and query rendering           | C3                 |
| Service set/unset                             | C3                 |
| Origin Path extraction                        | C3                 |
| Current Settings value                        | C4                 |
| Setting defaults and environment mapping      | C5                 |
| Method name -> field mapping                  | C6                 |
| String-or-null check of model values          | C6                 |
| Making Settings active / publishable          | C7                 |
| Any validation of Option values               | none (not done)    |

### 6.4 Who may raise or propagate errors

| Error                                   | Raised by | Propagated by         |
| --------------------------------------- | --------- | --------------------- |
| ERR-001 (service unset)                 | C3        | C1 -> Developer       |
| ERR-002 (undefined model call)          | X5 (Host) | C6 -> Developer       |
| ERR-003 (undefined disk)                | X1 (Host) | C2 -> C1 -> Developer |
| F-04 (non-string, non-null model value) | C6        | Developer             |

---

## 7. Data Flow

### 7.1 Data items and where they live

| Data                 | Created by                 | Held by                 | Lifetime                                     |
| -------------------- | -------------------------- | ----------------------- | -------------------------------------------- |
| Image Path           | Developer / model storage  | C1 (copy as given)      | life of the Builder                          |
| Option set           | Developer                  | C1                      | life of the Builder                          |
| Settings values      | C5 / Developer environment | X3                      | application lifetime; read fresh on each use |
| Source URL           | C2 (from X1/X2)            | local to one Resolution | one Resolution                               |
| Origin Path          | C3                         | local to one Resolution | one Resolution                               |
| Query string         | C3                         | local to one Resolution | one Resolution                               |
| Result (string/null) | C1                         | returned to Developer   | not retained                                 |

### 7.2 Flow: `storage()` with w present

```
C1: path non-empty
C1 -> C2 (path, storage)
  C2 -> C4 (disk)            => disk value | null
  C2 -> X1 (disk|none, path) => Source URL
C2 -> C1: Source URL
C1: w not null
C1 -> C3 (Source URL, Options)
  C3 -> C4 (service)         => value; unset -> ERR-001
  C3 -> C4 (base_url)        => value
  C3: Origin Path <- path component of Source URL
  C3: query <- present Options in order w,h,fit,q,f, raw
  C3: base_url + "/" + service + Origin Path + "?" + query
C3 -> C1: Forge URL
C1 -> Developer: Forge URL
```

### 7.3 Flow: `asset()` with w present

Identical to 7.2, except C2 does not read disk and obtains the Source URL from X2.

### 7.4 Flow: w null

```
C1: path non-empty
C1 -> C2 -> (X1 | X2) => Source URL
C1: w is null
C1 -> Developer: Source URL (unchanged)
```

C3 and the service/base_url Settings are not touched.

### 7.5 Flow: empty path

```
C1: path is null or ""
C1 -> Developer: null
```

No component other than C1 runs.

### 7.6 Flow: model call

```
Developer -> model.previewPathImageForge(320, h: 240)
C6: name matches declared field "preview_path"
C6 -> X4: raw value of "preview_path"
C6: value is string or null? else F-04
C6 -> C1 entry (value, w=320, h=240, others null)
C6 -> Developer: Builder
```

---

## 8. Failure Boundaries

| ID   | Failure                                               | Detected by | Result                                                                                                        | Containment rule                                                                                                      |
| ---- | ----------------------------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| F-01 | service null or empty when a Forge URL is needed      | C3          | ERR-001 raised; message contains `imageforge.service`                                                         | MUST NOT fall back to a default service, return a Source URL, or return null                                          |
| F-02 | disk setting names an undefined disk                  | X1          | Host error                                                                                                    | C2 and C1 MUST NOT catch, wrap, or replace it. Occurs even when w is null (Source URL is resolved before the w check) |
| F-03 | undefined model method name (not a declared field)    | X5 via C6   | Host's undefined-call error (ERR-002)                                                                         | C6 MUST NOT raise its own error or return a value                                                                     |
| F-04 | declared field's raw value is neither string nor null | C6          | error raised at the generated-method call, before a Builder is created. The message SHOULD name the attribute | MUST NOT coerce, convert, or treat as empty                                                                           |
| F-05 | any Host error from X1/X2 other than F-02             | X1/X2       | propagates                                                                                                    | same as F-02                                                                                                          |

Boundary rules:

- A failure aborts the current call. No partial URL is ever returned.
- Failures in one call leave no state behind. The next call is unaffected (no caches, no flags).
- Option values that Image Forge would treat specially (out of range, unknown fit/f, w=0) are never failures in this product. They pass through (FR-019).
- The product never contacts X6. Image Forge unavailability, unregistered services, or 404 responses are outside this product's failure boundary.

---

## 9. Architectural Rules

- R-01: Every decision has exactly one owner (section 6.3). A component MUST NOT make a decision owned by another component.
- R-02: Dependencies follow section 4 only. No component calls "up" or sideways.
- R-03: Only C5 reads environment values. Only C4 reads Settings during Resolution.
- R-04: Settings are read only during Resolution, never at Builder creation, never at registration, and never cached.
- R-05: A Builder never changes after creation. Calling `storage()` or `asset()` repeatedly on one Builder recomputes each time and does not retain results.
- R-06: URL building happens only inside C2 (Source URL) and C3 (Forge URL), and only during Resolution.
- R-07: C3 builds Forge URLs by literal concatenation. No normalization of any part.
- R-08: Host errors are propagated unchanged. This product never catches Host errors.
- R-09: Option values are carried verbatim from the caller to the query string: no coercion, clamping, encoding, defaulting, or validation, and no component is permitted to add any.
- R-10: C6 reads only raw stored values and only for declared fields.
- R-11: Both Strategies are always selected explicitly by the Developer's call. No component infers, defaults, or falls back between them.
- R-12: C6 interacts with the rest of the product only through the Builder entry (C1).
- R-13: Any behavior not assigned to a component in section 5 does not exist. New behavior requires assigning an owner first.

---

## 10. Non-Goals

Structures and responsibilities this architecture deliberately does not provide:

- No caching or memoization of URLs, Source URLs, Origin Paths, or Settings values anywhere.
- No state shared between Builders, between calls, or between models.
- No mutation operations on a Builder (no setters, no chaining that changes options, no "with" variants).
- No per-call override of base_url, service, or disk. Settings are global to the application.
- No support for more than one Service Identifier or Base URL per application at the same time.
- No component that contacts Image Forge (no health check, no existence check, no registration check).
- No URL parsing beyond reading the path component of a Source URL.
- No normalization layer for slashes, trailing slashes, encoding, or case in any URL part.
- No fallback chain between Strategies, between disks, or between Source URL and Forge URL on failure.
- No error translation layer. The product defines no error hierarchy beyond ERR-001 and F-04.
- No discovery of image fields by inspecting a model's attributes. Fields come only from the explicit declared list.
- No extension points: no events, hooks, callbacks, pluggable resolvers, or pluggable URL formats.
- No logging, metrics, or diagnostics output.
- No shared "options object" or preset concept between C1, C3, and C6; Options travel as five separate values.

---

## 11. Architectural Invariants

These MUST hold in every implementation and SHOULD each be testable.

- INV-01: Creating a Builder never reads Settings, never calls X1/X2, and never builds a URL (FR-004).
- INV-02: A null or empty-string Image Path always yields null from both `storage()` and `asset()`, regardless of w and regardless of Settings and Host state (no Settings read, no Host call).
- INV-03: With a non-empty Image Path and w null, the result equals the Host-supplied Source URL byte for byte, and `h`, `fit`, `q`, `f` have no effect on it.
- INV-04: With a non-empty Image Path and w not null (including 0), the result is a Forge URL, and its query string always begins with `w=`.
- INV-05: Settings `service` and `base_url` are read only when a Forge URL is being built.
- INV-06: The Forge URL equals base_url + "/" + service + Origin Path + "?" + query, with no characters added, removed, or altered.
- INV-07: The query string lists only non-null Options, in the order w, h, fit, q, f, as `name=value` pairs joined by `&`, with values exactly as supplied.
- INV-08: Origin Path equals the path component of the Source URL. The Source URL's query and fragment never appear in the Forge URL.
- INV-09: A null disk setting means the Host's default disk. Any non-null disk value is passed to the Host as the disk name unchanged (including the empty string).
- INV-10: A model call whose name is not a declared field's `{camelCase}ImageForge` is never handled by this product.
- INV-11: A generated method never reads through accessors or casts; it reads the raw stored value.
- INV-12: A generated method never returns a Builder for a value that is neither string nor null.
- INV-13: No method named `imageForge` is provided by the product.
- INV-14: Results of one call never influence a later call (no retained state).
- INV-15: The product makes no network request of its own.
- INV-16: Every component has a single owner for each decision in section 6.3; no decision appears in two components.

---

## 12. Resolved Decisions

### 12.1 Issues identified before writing

| Kind                       | Issue                                                                                             | Resolution                                                                                  |
| -------------------------- | ------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| Missing responsibility     | Nobody was assigned to orchestrate the Resolution sequence                                        | C1 (RD-07)                                                                                  |
| Missing responsibility     | Nobody was assigned to read Settings                                                              | C4; definition in C5 (RD-04)                                                                |
| Missing responsibility     | Nobody was assigned to extract the Origin Path                                                    | C3 (RD-09)                                                                                  |
| Missing responsibility     | Nobody was assigned to check the model value type                                                 | C6 (RD-03)                                                                                  |
| Conflicting responsibility | Generated methods must exist (FR-033) yet ERR-002 requires the Host to raise for undeclared names | C6 handles only declared names and hands everything else back to the Host unchanged (RD-13) |
| Ambiguous flow             | Order when both ERR-001 and ERR-003 conditions are present                                        | RD-05                                                                                       |
| Ambiguous flow             | When Settings are read                                                                            | RD-04                                                                                       |
| Ambiguous flow             | Whether Option values are encoded                                                                 | RD-01                                                                                       |
| Ambiguous flow             | Which stored value generated methods use                                                          | RD-02                                                                                       |
| Ambiguous flow             | Non-string model values                                                                           | RD-03                                                                                       |

### 12.2 Decisions

**RD-01 Option value rendering**

- Ambiguous: FR-019 says values pass "without modification"; it does not say whether characters like `&` or `#` are encoded when the query string is built.
- Decided (by the project owner): values are rendered raw with no encoding.
- Why: matches FR-019 literally. Consequence accepted: a value containing `&` or `#` corrupts the URL structure; the Developer is responsible for supplying URL-safe values.

**RD-02 Which attribute value generated methods use**

- Ambiguous: EL-006 and FR-034 say "the attribute value" without saying raw or accessor-processed.
- Decided (by the project owner): the raw stored value, ignoring accessors.
- Why: keeps the Image Path equal to what the database holds (the relative path, per the spec's Image Path definition) and keeps the product independent of model casts (FR-039).

**RD-03 Non-string, non-null model value**

- Ambiguous: Inputs define Image Path as string or null; behavior for other types is not specified.
- Decided (by the project owner): an error is raised.
- Where enforced: C6, at the generated-method call, before the Builder exists. Not extended to direct calls of the Builder entry, whose input contract is defined by PRODUCT_SPEC Inputs and which performs no validation (R-09, R-13). Behavior for non-string, non-null input passed directly to the Builder entry is therefore unspecified and MUST NOT be asserted by tests.
- Why: FR-001 and FR-004 say the Builder entry always returns a Builder and builds nothing; adding type enforcement there would extend the decision beyond what was approved.
- Note: the empty string is a string and is valid; it yields null at Resolution (INV-02).

**RD-04 Settings read timing**

- Ambiguous: PRODUCT_SPEC does not say whether Settings are captured at Builder creation or read at Resolution.
- Decided: read at Resolution, each time, through C4, with no caching.
- Why: FR-004 forbids URL building at creation; reading at Resolution keeps creation side-effect free and makes tests that change Settings between calls deterministic.

**RD-05 Error precedence**

- Ambiguous: if the disk is undefined (ERR-003) and the service is unset (ERR-001) and w is present, which error occurs is not specified.
- Decided: ERR-003 first, because the Source URL is resolved (step 2) before the Forge URL is composed (step 4).
- Why: the Source URL must be resolved in the w-null case anyway (FR-007), so one fixed order for both cases is simplest.
- Consequence: with w null, the service is never checked (consistent with AC-012).

**RD-06 Owner of the service-unset check**

- Ambiguous: ERR-001 is stated for "a Forge URL is requested" without naming where.
- Decided: C3 raises it, at step 4, when it reads service.
- Why: C3 is the only component that uses service; the check lives with the use.

**RD-07 Resolution orchestrator**

- Ambiguous: the spec lists interactions per entry point but no sequencing owner.
- Decided: C1 owns the step order (section 6.1). C2 and C3 are called by C1 and never by each other.
- Why: one owner for sequencing; C2 and C3 stay single-purpose.

**RD-08 Literal concatenation**

- Ambiguous: the spec formula `Base URL + "/" + Service Identifier + Origin Path` does not say what happens with a trailing slash in Base URL, or an Origin Path without a leading slash.
- Decided: literal concatenation, no normalization (R-07, INV-06).
- Why: any normalization would be an unspecified behavior. Consequence accepted: Base URL with a trailing slash yields a double slash; Developer responsibility.

**RD-09 Source URL query and fragment**

- Ambiguous: whether a Source URL's own query or fragment (for example from a signed URL) carries into the Forge URL.
- Decided: discarded. Origin Path is defined in PRODUCT_SPEC as the URL path component, and Forge URL structure contains only Origin Path plus the Option query.
- Why: follows the spec definitions literally.

**RD-10 Meaning of "w present"**

- Ambiguous: whether w = 0 counts as absent.
- Decided: only null means absent. w = 0 is present and yields a Forge URL containing `w=0` (FR-018, AC-007).
- Why: explicit in the spec; recorded so implementations do not use a truthiness test.

**RD-11 Options other than w when w is null**

- Ambiguous: whether h, fit, q, f alone should produce a Forge URL.
- Decided: no. With w null the Passthrough result is returned and all other Options are ignored.
- Why: FR-007 and FR-011 state this without exception. Recorded because it may look like a defect to an implementer.

**RD-12 Meaning of an empty-string disk setting**

- Ambiguous: FR-020 and FR-021 distinguish null from "not null" only.
- Decided: only null selects the Host default disk. An empty string is a disk name and is passed to the Host (which will raise ERR-003).
- Why: literal reading of FR-020 and FR-021.

**RD-13 Method name matching**

- Ambiguous: the spec gives the pattern `{camelCaseFieldName}ImageForge` and two examples but no matching algorithm.
- Decided: section 6.2 (exact, case-sensitive, camelCase by underscore splitting). Names not matching are handed back to the Host unchanged.
- Why: the two spec examples fit this rule; ERR-002 requires the Host to raise for everything else.
- Left undefined: if two declared fields produce the same method name, which one is used is unspecified and not a supported configuration; tests MUST NOT assert it.

**RD-14 Absent field declaration**

- Ambiguous: behavior of a model that uses Field Binding but declares no field list.
- Decided: treated as an empty list. Every `...ImageForge` call is undeclared and is handled per ERR-002.
- Why: follows from ERR-002 (not in the list -> not handled).

**RD-15 Builder holds no results**

- Ambiguous: whether the Builder may memoize its URL.
- Decided: it MUST NOT (R-05, INV-14).
- Why: Settings are read fresh (RD-04); memoized results would go stale.
