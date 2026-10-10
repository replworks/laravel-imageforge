# TASKS

Execution checklist in dependency order. Each task is completed by one AI in one session, then work stops (AGENTS.md).
Spec IDs (FR-, ERR-, AC-, SCR-, EL-) refer to PRODUCT_SPEC.md. INV-, F-, RD-, R- IDs and component names refer to ARCHITECTURE.md.
"Unit tests passing" means the unit tests written for that task pass.

- [X] T-001 Project foundation
  - Satisfies: PRODUCT_SPEC.md Constraints (Laravel and PHP requirements)
  - External boundary: no
  - Acceptance criteria:
    - Every constraint in TECH_STACK.md is satisfied.
    - Every check defined in TECH_STACK.md passes.
    - AC-026 passes.
    - AC-028 passes.

- [X] T-002 Developer configures the product through environment variables and an optional published config
  - Satisfies: PRODUCT_SPEC.md SCR-003, SCR-004 (EL-010); FR-022, FR-023, FR-024, FR-025, FR-026, FR-027, FR-028, FR-029, FR-030
  - External boundary: no
  - Acceptance criteria:
    - AC-023 passes.
    - AC-024 passes.
    - Without any publishing step, the base URL setting defaults to the value in FR-022, the service setting has no value (FR-023), and the disk setting is null (FR-024).
    - Defining each of the three environment variables sets its corresponding setting (FR-026, FR-027, FR-028).
    - The product's settings are available in an application that has not registered anything manually (FR-030).
    - Environment values are read only by the Settings Definition (ARCHITECTURE.md R-03).
    - Unit tests passing.

- [X] T-003 Developer obtains a Source URL, or null, from a Builder
  - Satisfies: PRODUCT_SPEC.md SCR-001 (EL-001, EL-002, EL-003); FR-001, FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-009, FR-010, FR-011, FR-020, FR-021, FR-032, FR-040
  - External boundary: no
  - Acceptance criteria:
    - AC-005 passes.
    - AC-006 passes.
    - AC-008 passes.
    - AC-009 passes.
    - AC-012 passes.
    - AC-014 passes with w absent.
    - AC-015 passes with w absent.
    - AC-016 passes with w absent; the Host error propagates unchanged (ARCHITECTURE.md F-02, F-05).
    - AC-025 passes.
    - Creating a Builder with an undefined disk setting and an unset service setting raises no error (ARCHITECTURE.md INV-01).
    - A null or empty Image Path returns null from both resolution calls without reading any setting (ARCHITECTURE.md INV-02).
    - With w absent, h, fit, q and f supplied together still return the Source URL unchanged (ARCHITECTURE.md INV-03, RD-11).
    - A builder works without any model integration (FR-040).
    - Unit tests passing.

- [ ] T-004 Developer obtains a Forge URL from a storage-based Builder
  - Satisfies: PRODUCT_SPEC.md SCR-001 (EL-002); FR-008, FR-013, FR-014, FR-015, FR-018; ERR-001; ERR-003
  - External boundary: no
  - Acceptance criteria:
    - AC-001 passes.
    - AC-007 passes.
    - AC-011 passes; the error is raised by the Forge URL Composer and no Source URL or null is returned instead (ARCHITECTURE.md F-01).
    - AC-013 passes.
    - AC-016 passes with w present; the Host error propagates unchanged (ARCHITECTURE.md F-02, RD-05).
    - The Forge URL equals base URL + "/" + service + Origin Path + "?" + query with nothing added, removed or altered (ARCHITECTURE.md INV-06, RD-08).
    - The Source URL's own query and fragment do not appear in the Forge URL (ARCHITECTURE.md INV-08).
    - Changing a setting between two calls on the same Builder changes the second result (ARCHITECTURE.md R-04, R-05, INV-14).
    - Unit tests passing.

- [ ] T-005 Developer controls the Forge URL query with h, fit, q and f
  - Satisfies: PRODUCT_SPEC.md Forge URL structure (Outputs); FR-016, FR-017, FR-019
  - External boundary: no
  - Acceptance criteria:
    - AC-003 passes.
    - AC-004 passes.
    - AC-010 passes.
    - The query lists only non-null options, in the order w, h, fit, q, f (ARCHITECTURE.md INV-07).
    - An option value containing characters reserved in URLs appears in the query exactly as supplied (ARCHITECTURE.md RD-01).
    - Option values the Image Forge service treats specially (out of range, unknown fit or f) produce no error in the product (ARCHITECTURE.md Section 8).
    - Unit tests passing.

- [ ] T-006 Developer obtains a Forge URL from an asset-based Builder
  - Satisfies: PRODUCT_SPEC.md SCR-001 (EL-003); FR-012; ERR-001
  - External boundary: no
  - Acceptance criteria:
    - AC-002 passes.
    - With the service setting unset and w present, the call raises the ERR-001 error (ARCHITECTURE.md F-01).
    - With the disk setting naming an undefined disk, the asset-based call still returns its result (the disk setting is not used by this strategy; ARCHITECTURE.md Source URL Resolver).
    - The result's Origin Path comes from the asset-based Source URL, not the storage-based one (FR-015).
    - Unit tests passing.

- [ ] T-007 Developer calls a generated method per declared model field
  - Satisfies: PRODUCT_SPEC.md SCR-002 (EL-004, EL-005, EL-006); FR-031, FR-033, FR-034, FR-035, FR-036, FR-039
  - External boundary: no
  - Acceptance criteria:
    - AC-017 passes.
    - AC-018 passes.
    - AC-019 passes.
    - AC-022 passes.
    - A generated method uses the raw stored value even when the model defines an accessor for that attribute (ARCHITECTURE.md INV-11, RD-02).
    - A declared field whose raw value is neither a string nor null raises an error at the generated-method call and returns no Builder (ARCHITECTURE.md F-04, INV-12, RD-03).
    - A declared field whose raw value is null or the empty string returns a Builder whose resolution returns null (ARCHITECTURE.md INV-02).
    - Method-name matching follows ARCHITECTURE.md Section 6.2.
    - Unit tests passing.

- [ ] T-008 Calls for undeclared fields and for a default-field method are not handled by the product
  - Satisfies: PRODUCT_SPEC.md SCR-002; FR-037, FR-038; ERR-002
  - External boundary: no
  - Acceptance criteria:
    - AC-020 passes.
    - AC-021 passes.
    - For an undeclared name, the error comes from the Host and the product raises no error of its own (ARCHITECTURE.md F-03, INV-10).
    - A model that uses the integration but declares no fields handles every such call as ERR-002 (ARCHITECTURE.md RD-14).
    - Unit tests passing.

- [ ] T-009 README first-line slot states the package is a client for the Image Forge service
  - Satisfies: PRODUCT_SPEC.md FR-041
  - External boundary: no
  - Acceptance criteria:
    - AC-027 passes.
    - The wording beyond what AC-027 checks is not part of this task (human-owned, AGENTS.md HUMAN_OWNED_CHANGES).
