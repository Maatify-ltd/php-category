# DEC-003 — Discover current mutation scopes under Host transactions

## Decision Metadata

| Field | Value |
|---|---|
| Decision ID | DEC-003 |
| Title | Discover current mutation scopes under Host transactions |
| Status | ACTIVE |
| Scope / Concern | Order and Image Assignment default lock boundaries |
| Decision Authority / Deciders | Project Owner |
| Canonical Contract / Current Owner | [Category Package Reference](../../CATEGORY_PACKAGE_REFERENCE.md) |

## Context

A Host transaction using MySQL `REPEATABLE READ` can hold an older snapshot
while a locking read sees a new row or newer Category parent. Plain scope
discovery could falsely report a missing row or use an obsolete sibling scope.

## Decision

Use current reads on the Host PDO for Category, Content Field, and Image
Assignment reorder scope discovery after their existing service target locks.
Image Assignment default changes retain scope-before-target locking. To
discover an immutable scope that the Host snapshot misses, allow a lazy
autocommit read on a second PDO from the same primary and schema. The Host
transaction still performs the authoritative scope lock and target check.

## Rationale

Category parents can change, so a current read must resolve their scope.
A locking lookup before Image Assignment default scope discovery would
invert its existing lock order. A separate non-locking discovery read keeps
that order, and the first read on the Host PDO can see its own uncommitted writes.

## Consequences

Callers can use `CategoryFactory::create()` for one-connection composition.
When default changes must see assignments committed after a Host snapshot,
the Host configures the dedicated lookup through `CategoryFactory::forHost()`.
This does not change transaction ownership or the creation/reorder lock order.
