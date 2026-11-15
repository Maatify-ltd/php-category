# DEC-006 — Allow forward PHP 8 Composer eligibility

## Decision Metadata

| Field | Value |
|---|---|
| Decision ID | DEC-006 |
| Title | Allow forward PHP 8 Composer eligibility |
| Status | ACTIVE |
| Scope / Concern | Composer interpreter eligibility and Stable PHP qualification |
| Decision Authority / Deciders | Project Owner, approving forward eligibility within PHP 8 |
| Canonical Contract / Current Owner | [Composer manifest](../../composer.json) and [Category Package Reference](../../CATEGORY_PACKAGE_REFERENCE.md#composer-and-platform-contract) |
| Supersedes | [DEC-002](DEC-002-php-security-patch-floors.md) |

## Context

[DEC-002](DEC-002-php-security-patch-floors.md) enforced the approved PHP
patch floors and scoped the candidate's Composer range to PHP 8.4 and 8.5.
The central Composer and CI standards now allow forward eligibility within
PHP 8 and define how to qualify newly Stable minors.

## Decision

For RC.2, declare `>=8.4.27 <8.5 || >=8.5.12 <9.0` in Composer and keep
the development solver platform at `8.4.27`. Verify the actual Stable
interpreter in PHP 8.4 and 8.5 CI jobs. Simulate forward versions using
Composer to verify the public install-time boundary.

## Rationale

The disjoint ranges preserve both approved patch floors and give consumers
forward eligibility within PHP 8. The actual-interpreter check and test
matrix provide evidence for supported Stable PHP versions. The simulated
Composer checks cover eligible versions before a Stable interpreter is
available.

## Consequences

Verify an eligible minor once it reaches Stable before qualifying a
subsequent release that admits it. Record the actual patch, applicable
official security evidence, and successful compatibility checks. Published
Category RC.1 retains the Composer contract at its original tag.
