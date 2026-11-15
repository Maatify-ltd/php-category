# DEC-002 — Enforce the approved PHP security patch floors in Composer

## Decision Metadata

| Field | Value |
|---|---|
| Decision ID | DEC-002 |
| Title | Enforce the approved PHP security patch floors in Composer |
| Status | SUPERSEDED |
| Superseded By | [DEC-006](DEC-006-php-forward-eligibility.md) |
| Scope / Concern | PHP runtime compatibility and dependency resolution |
| Decision Authority / Deciders | Project Owner, authorizing the approved PHP series and patch floors |
| Canonical Contract / Current Owner | [Composer manifest](../../composer.json) and [Category Package Reference](../../CATEGORY_PACKAGE_REFERENCE.md#composer-and-platform-contract) |

## Context

Published Category RC.1 declares PHP `^8.4`. The checkout already enforced
minimum PHP patches in CI, but its Composer constraint still admitted
interpreters outside those approved boundaries when installed by a consumer.

## Decision

The checkout declares `>=8.4.27 <8.5 || >=8.5.12 <8.6` in Composer. Use
`8.4.27` as the development solver platform, test the accepted and rejected
boundaries through Composer, and enforce the actual interpreter floor in CI.

## Rationale

An exact floor for each approved series lets Composer reject unsupported
runtime versions during installation. Resolving development dependencies
against the lowest admitted patch checks compatibility with the full
declared range. An actual interpreter check complements Composer's
simulated platform and covers CI configuration drift.

## Consequences

Consumers of the checkout need an approved PHP series and patch to resolve
Category. The published RC.1 tag retains its existing requirements. A future
release carrying the narrower contract needs release qualification and Owner
authorization.
