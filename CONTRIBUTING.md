# Contributing — maatify/php-category

## Scope

Keep this repository framework-neutral and focused on the reusable Category,
Category Content, Content Field, Image Role, and Image Assignment capabilities.
Their domain APIs, thin package Facade, and framework-neutral Factory are
package-owned boundaries. Do not add Catalog, Product, Admin/Slim, presentation,
or Host-specific integrations to this package.

The stable package contract is [CATEGORY_PACKAGE_REFERENCE.md](CATEGORY_PACKAGE_REFERENCE.md).
Detailed architecture notes belong under `docs/` and must link back to that
reference.

## Ways to contribute

- Report a reproducible bug or propose a focused documentation improvement.
- Submit a narrowly scoped change with the required Unit and real-engine
  Integration coverage when behavior changes in any owned capability.
- Discuss architecture or public-contract changes before implementation when
  they affect package boundaries, dependencies, schema, or compatibility.

## Local verification

Work from a repository checkout with PHP, Composer 2.11.2, Git, Bash, jq, and Docker
with Compose v2 available. Run
these checks from the repository root:

```bash
php scripts/ci/check-php-patch-floor.php 8.4 # use 8.5 when running that minor
bash scripts/ci/check-composer-php-boundaries.sh
composer validate --strict
composer update --no-interaction --prefer-dist --no-progress --prefer-stable
# For the lowest-dependency CI job, add --prefer-lowest to this update.
bash scripts/ci/check-package-php-syntax.sh
composer dump-autoload --optimize --strict-psr
composer check-platform-reqs
composer audit --no-interaction --abandoned=fail
composer check:style
composer analyse
composer test:unit
bash scripts/ci/check-package-archives.sh HEAD
vendor/bin/phpstan analyse examples --level=max --memory-limit=256M
bash scripts/ci/with-category-mysql.sh --install-schema php examples/category-workflow.php
CATEGORY_MYSQL_VERSION=8.0.16 CATEGORY_MIGRATION_EXPECTED_MYSQL_VERSION=8.0.16 \
    bash scripts/ci/with-category-mysql.sh bash scripts/ci/check-scope-equality-migration.sh
bash scripts/ci/with-category-mysql.sh composer test:integration
bash scripts/ci/with-category-mysql.sh composer test
```

The patch-floor check logs the actual interpreter and verifies Stable PHP 8.4
or 8.5 against its approved floor. The boundary probe reads `require.php` and
uses Composer to check both patch floors, simulated forward PHP 8.6 and 8.7
eligibility, and rejection outside the range. See
[release qualification](docs/guides/PACKAGE_ARCHIVE_VERIFICATION.md#prepared-rc2-verification)
for the checks required when a forward-eligible PHP minor reaches Stable.

`composer check:style` checks package-owned PHP against PHP-FIG PER-CS 3.1
using the PHP CS Fixer 3.0 rule set and a verifier for the 3.1 changes.
`composer format` applies the fixer rules. Address any remaining 3.1 findings
reported by the check, then rerun it. If the checker cannot classify a clear
top-level comment that deliberately falls into the next case, submit a reviewed
entry in `scripts/ci/per-cs-31-fallthrough-approved.json`. Use `file` for the
repository-relative PHP path, `line` for the case line, `case` for the exact case
label through its colon, and `comment` for the exact comment text without its
delimiters. Normalize a multiline comment to a single line. An entry applies only
at that location and cannot replace termination of the final case.

The MySQL wrapper uses the checkout’s `tests/compose.integration.yaml`.
It waits for a real MySQL query to succeed, discovers the disposable loopback
port, and disposes of the database and its volumes after each run. The pinned
default is MySQL 8.0.16. Use `CATEGORY_MYSQL_VERSION` and
`CATEGORY_MYSQL_PLATFORM` to select a supported verification image when
needed. Integration tests and the consumer install the schema from their own
package boundaries. The public example receives the installed schema through
`--install-schema`.

The migration check uses the RC.1 schema at `57d160f` from
`tests/Fixtures/Migration/` in the checkout. It verifies conflict
refusal before any DDL, data retention, partial migration recovery and schema
parity with a fresh install. CI requires the actual MySQL 8.0.16 server for this
check.

Run the external harness twice, as in CI. Each invocation installs the current
checkout commit through Composer in a fresh consumer root. It checks production
autoload, the Persistence version and distribution, a Host rollback, and
concurrent creation on real MySQL.

```bash
for run in 1 2; do
    bash scripts/ci/with-category-mysql.sh bash scripts/ci/run-standalone-consumer.sh
done
```

For workflow lint and the package-owned whitespace check, use:

```bash
docker run --rm -v "$PWD:/repo" -w /repo rhysd/actionlint@sha256:887a259a5a534f3c4f36cb02dca341673c6089431057242cdc931e9f133147e9
bash scripts/ci/test-package-whitespace.sh
: "${BASE_SHA:?Set BASE_SHA to the full integration base commit SHA}"
bash scripts/ci/check-package-whitespace.sh workflow_dispatch "$BASE_SHA" "$(git rev-parse HEAD)"
```

For local or manually started checks, choose the full commit SHA at the intended
integration boundary, such as the phase branch base or `main` as appropriate.
Pass it as `BASE_SHA` locally or as the `base_sha` workflow input on GitHub.
It must identify a commit strictly before HEAD in its history. A missing base,
HEAD itself, or a commit outside that history fails verification.

The package does not commit `composer.lock`; Composer may create it temporarily
during local verification. Remove it before committing.

## Pull Request expectations

- Keep each PR within one clearly stated scope and follow the Phase Stack
  workflow in the pinned engineering standards.
- Do not add unrelated refactors, Host integrations, or framework bindings.
- Describe the starting branch, changed files, verification results, and any
  remaining limitation or deferred work.
- Do not merge directly to `main`; the repository owner approves the final
  merge.

## Change requirements

- Preserve public typed contracts and naming suffixes.
- Add or update Unit and real-engine Integration coverage for behavior changes.
- Keep PHPStan at level max with zero baseline or hidden suppressions.
- Update `CATEGORY_PACKAGE_REFERENCE.md` only when the stable package contract
  changes; put detailed architecture notes under `docs/`.

## Security and architecture discussions

Do not report vulnerabilities in a public issue. Use the private reporting route
described in [SECURITY.md](SECURITY.md).

Architecture changes that affect package boundaries, public contracts,
Composer dependencies, schema, persistence behavior, or host isolation require
an explicit discussion and an approved decision before implementation.
