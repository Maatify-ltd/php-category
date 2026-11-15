# DEC-001 — Adopt the published Persistence RC in Category

## Decision Metadata

| Field | Value |
|---|---|
| Decision ID | DEC-001 |
| Title | Adopt the published Persistence RC in Category |
| Status | ACTIVE |
| Scope / Concern | Shared Ordering, Pagination, and Transactions |
| Decision Authority / Deciders | Project Owner, authorizing migration to the published replacement RC |
| Canonical Contract / Current Owner | [Category Package Reference](../../CATEGORY_PACKAGE_REFERENCE.md) |

## Context

Published Category RC.1 uses the abandoned `maatify/persistence` package.
The replacement is available at the exact published version
`maatify/php-persistence 1.0.0-rc.1`.

## Decision

Pin the checkout to the replacement RC at
`34c3b874525d36ae064c162cdd553bc75d18772b`. The
[release tag](https://github.com/Maatify-ltd/php-persistence/tree/v1.0.0-rc.1)
and [Composer distribution metadata](https://repo.packagist.org/p2/maatify/php-persistence.json)
identify the same revision. Keep the root Composer stability policy at
`stable` and allow the exact RC in the standalone consumer. The runner and
repositories continue to share the Host PDO connection.

Category, Image Assignment, and Content Field creation retain their scope
locks and use the shared current read in an active transaction.

## Rationale

Pinning the exact published RC gives the candidate and the standalone consumer
the same reviewed dependency and distribution reference. The replacement has
a published RC, so migration can be verified now while Stable qualification
remains a separate release decision.

A scope lock serializes allocation but does not refresh an existing Host
transaction snapshot. The transaction-aware current read observes the latest
committed positions after the lock is acquired, preventing an allocation based
on stale ordering data while leaving commit and rollback under Host control.

## Consequences

Callers using the shared public types migrate to
`Maatify\PhpPersistence\Pagination\Criteria\PageRequest`,
`Maatify\PhpPersistence\Pagination\DTO\PageResultDTO` and the corresponding
Gen2 Ordering and Transaction types. The application and schema retain their
behavioral boundaries. The published Category RC.1 tag retains its original
dependency.

This decision covers the candidate checkout. A future Category RC using this
dependency requires release qualification and Owner authorization. Category
Stable requires a compatible published Persistence Stable.
