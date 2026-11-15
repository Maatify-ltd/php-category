# DEC-004 — Share MySQL scope equality between reads and mutations

## Decision Metadata

| Field | Value |
|---|---|
| Decision ID | DEC-004 |
| Title | Share MySQL scope equality between reads and mutations |
| Status | ACTIVE |
| Scope / Concern | Image Assignment and Content Field identity, ordering, defaults, and upgrades |
| Decision Authority / Deciders | Project Owner, authorizing remediation of verified Category contract gaps |
| Canonical Contract / Current Owner | [Category Package Reference](../../CATEGORY_PACKAGE_REFERENCE.md) and [Category schema](../../schema/README.md) |

## Context

The published scope predicates compare language and platform with
`utf8mb4_unicode_ci`. The previous generated ordering key encoded the length of
the supplied string, so values equal to MySQL, such as `en` and `en `, could
receive separate ordering positions and both match the same consumer read.
Image Assignment could consequently expose two defaults in that scope.

## Decision

Use the same collation for scope selection, stable identity, ordering and Image
Assignment default enforcement. For each non-null text dimension, derive an
indexed digest from MySQL collation weights after removing trailing pad-space
weights. Encode SQL NULL separately from every digest. Compute the same key on
MySQL for bound creation inputs before obtaining the insertion scope lock.
Retain the original language/platform values and the existing read predicates.

## Rationale

MySQL owns the equality used by its predicates. Collation weights preserve
case, accent, and pad-space equivalence without reconstructing collation rules
in PHP or changing existing reads. Fixed-size digests bound index size and make
scope composition independent of separators in Host strings. An explicit
NULL token keeps the nullable dimension distinct even for non-null values with
no significant weights. The upgrade preflight gives the Host explicit control
over collisions in stored defaults and ordering.

## Consequences

The updated application needs the corresponding generated column definitions.
Existing installations follow the [schema upgrade](../../schema/README.md#upgrade-an-existing-image-assignment-or-content-field-schema)
with writes paused. The migration reports conflicts and retains stored
business data. Relative ordering within one scope continues to use display
order followed by identity, including when historical same-scope positions
tie.
