<div align="center">

# Maatify Category

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://www.maatify.dev/company-profile/maatify_logo_white.svg">
  <source media="(prefers-color-scheme: light)" srcset="https://www.maatify.dev/company-profile/maatify_logo_dark.svg">
  <img alt="Maatify.dev" src="https://www.maatify.dev/company-profile/maatify_logo_dark.svg">
</picture>

**Package Status**

[![Status](https://img.shields.io/badge/Status-Release%20Candidate-blue)](CHANGELOG.md)
[![Version](https://img.shields.io/badge/Version-v1.0.0--rc.1-blue)](https://packagist.org/packages/maatify/php-category#v1.0.0-rc.1)
[![PHP](https://img.shields.io/badge/PHP-8.4.27%E2%80%938.4.x%20%2F%208.5.12%E2%80%938.5.x-8892BF)](composer.json)
[![Checkout License](https://img.shields.io/badge/License-Proprietary-green)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-2F9E44.svg)](CONTRIBUTING.md#local-verification)

**Ecosystem / Adoption**

[![Packagist](https://img.shields.io/badge/Packagist-maatify%2Fphp--category-F28D1A)](https://packagist.org/packages/maatify/php-category)
[![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/php-category)](https://packagist.org/packages/maatify/php-category)
[![Total Downloads](https://img.shields.io/packagist/dt/maatify/php-category)](https://packagist.org/packages/maatify/php-category)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet)](https://github.com/Maatify-ltd)
[![Install](https://img.shields.io/badge/Install-maatify%2Fphp--category%3A1.0.0--rc.1-blue)](https://packagist.org/packages/maatify/php-category#v1.0.0-rc.1)

**Documentation**

[![Usage Guide](https://img.shields.io/badge/Usage%20Guide-Read-blue)](docs/guides/USAGE_GUIDE.md)
[![Examples](https://img.shields.io/badge/Examples-View-blue)](examples/)
[![Package Reference](https://img.shields.io/badge/Reference-Read-blue)](CATEGORY_PACKAGE_REFERENCE.md)
[![Changelog](https://img.shields.io/badge/Changelog-View-blue)](CHANGELOG.md)
[![Security Policy](https://img.shields.io/badge/Security-Policy-blue)](SECURITY.md)
[![Contributing Guide](https://img.shields.io/badge/Contributing-Guide-blue)](CONTRIBUTING.md)

Framework-neutral PHP contracts and MySQL storage for hierarchical categories,
content, extensible fields, image roles, and assignments to Host-owned media.

**Install the published RC.1:** `composer require maatify/php-category:1.0.0-rc.1`

</div>

---

## Package Summary

`maatify/php-category` provides typed commands, repositories, and public read
APIs for managing a Category tree and its related content.

## Key Features

- Hierarchy, lifecycle, and deterministic ordering of Category records.
- Localized or unlocalized name and description, plus Host-defined fields with exact scopes.
- Host-defined image roles and exact-scope assignments to existing Media Asset IDs.
- Separate consumer visibility and management reads, with bounded lists and management pagination.

## Requirements

The current checkout requires PHP 8.4.27 or later in the 8.4 series, or
PHP 8.5.12 or later in the 8.5 series, plus `ext-mbstring`, `ext-pdo`, and
`ext-pdo_mysql`. It uses MySQL 8.0.16+ with InnoDB and enforced `CHECK`
constraints. Runtime dependencies are `maatify/exceptions`, `maatify/shared-common`,
and the exact `maatify/php-persistence 1.0.0-rc.1` trial. See [composer.json](composer.json)
for the declared constraints and [local verification](CONTRIBUTING.md#local-verification)
for the Composer and runtime checks.

## Installation

The published Category RC.1 retains its original PHP constraint, Persistence
dependency, and [MIT license](https://github.com/Maatify-ltd/php-category/blob/v1.0.0-rc.1/LICENSE).
Use the [published tag documentation](https://github.com/Maatify-ltd/php-category/tree/v1.0.0-rc.1)
when installing that exact version.

```bash
composer require maatify/php-category:1.0.0-rc.1
```

The current checkout uses the replacement Persistence RC for Ordering,
Pagination, and Transactions. A Composer root consuming the checkout must
explicitly allow `maatify/php-persistence:1.0.0-rc.1@RC` while retaining
`minimum-stability: stable`. See [DEC-001](docs/decisions/DEC-001-persistence-rc-migration.md)
for the migration context.

## Quick Usage

Install the [Category schema](schema/README.md) in the Host database. Pass the
Host's PDO and clock to the factory:

```php
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\Factory\CategoryFactory;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;

$category = CategoryFactory::create($pdo, $clock);
$categoryId = $category->categories()->create(new CreateCategoryCommand('clothing'));
$category->contents()->create(new CreateCategoryContentCommand($categoryId, null, 'Clothing', null));
$visibleCategory = $category->categories()->getById($categoryId);
```

See the [Usage Guide](docs/guides/USAGE_GUIDE.md) and [runnable example](examples/category-workflow.php)
for workflow steps and input, result, and ownership boundaries.

## Public Runtime API

`CategoryFactory` returns one facade with five APIs. `categories()` manages the
tree, `contents()` manages name/description records, `contentFields()` manages
structured field values, `imageRoles()` manages the role registry, and
`images()` manages assignments. The [Category Package Reference](CATEGORY_PACKAGE_REFERENCE.md)
defines the complete public inventory and persistence contract.

## Critical Runtime Behavior

Consumer reads enforce active, non-deleted ancestor visibility. Management
reads expose typed filters for administrative inspection. For exact scopes,
a null language, platform, or role means the null value itself. Locale
fallback and Media Asset resolution are Host responsibilities.

The Host owns the PDO connection, outer transaction, and clock. Services
reuse that connection and preserve Host commit and rollback. The package
classifies selected storage errors. Unclassified driver failures can
propagate as `PDOException`; the Host maps errors to its transport or UI.

## Documentation

- [Consumer Usage Guide](docs/guides/USAGE_GUIDE.md)
- [Executable examples](examples/)
- [Category Package Reference](CATEGORY_PACKAGE_REFERENCE.md)
- [Schema setup](schema/README.md)
- [Changelog](CHANGELOG.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)

## Quality Status

See [local verification](CONTRIBUTING.md#local-verification) for Composer,
static analysis, code style, MySQL tests, and external Composer consumer
verification. Category is pre-Stable. See the [Security Policy](SECURITY.md)
for support status.

## License

This checkout is proprietary to Maatify Ltd. Obtain written authorization
before use by requesting a free license from
[support@maatify.dev](mailto:support@maatify.dev). Include your organization
name, if applicable, and an email address for the person who should receive
security advisories and critical update notices. Maatify Ltd. uses those
details to administer licenses and send those notices. See [LICENSE](LICENSE)
for the terms.

## Author

Engineered by **Mohamed Abdulalim** ([@megyptm](https://github.com/megyptm))<br>
Backend Lead & Technical Architect<br>
[https://www.maatify.dev](https://www.maatify.dev)

---

<div align="center">

[Built with ❤️ by Maatify.dev — Unified Ecosystem for Modern PHP Libraries](https://www.maatify.dev)

</div>
