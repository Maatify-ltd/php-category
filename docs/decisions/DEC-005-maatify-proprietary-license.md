# DEC-005 — Adopt the Maatify Proprietary License

## Decision Metadata

| Field | Value |
|---|---|
| Decision ID | DEC-005 |
| Title | Adopt the Maatify Proprietary License |
| Status | ACTIVE |
| Scope / Concern | Category licensing and consumer-facing license presentation |
| Decision Authority / Deciders | Project Owner, confirming Maatify Ltd. as rights holder and adopting the approved written-authorization terms |
| Canonical Contract / Current Owner | [LICENSE](../../LICENSE) |

## Context

Maatify Ltd. holds the rights to Category. The published
[Category RC.1 tag](https://github.com/Maatify-ltd/php-category/tree/v1.0.0-rc.1)
distributed the package under MIT. The Owner approved applying the same
proprietary license with no-charge written authorization used by the other
Maatify packages.

## Decision

Apply the Maatify Proprietary License to the current Category source.
Maatify Ltd. issues written authorization free of charge and maintains the
license-holder register. Use Composer's `proprietary` identifier. The README
identifies the applicable terms, request contact, and information to provide.

## Rationale

The approved terms keep the license free of charge and give Maatify Ltd. a
designated contact for license administration, security advisories, and
critical update notices. Matching the README and Composer metadata to the
root license lets consumers identify applicable terms before use.

## Consequences

Consumers of the current source must request written authorization under
the terms in `LICENSE`. Published version `v1.0.0-rc.1` retains the MIT
license it distributed. A subsequent release requires separate Owner
authorization and verification of the license in its distribution archive.
