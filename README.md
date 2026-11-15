<div align="center">

# Maatify Category

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://www.maatify.dev/company-profile/maatify_logo_white.svg">
  <source media="(prefers-color-scheme: light)" srcset="https://www.maatify.dev/company-profile/maatify_logo_dark.svg">
  <img alt="Maatify.dev" src="https://www.maatify.dev/company-profile/maatify_logo_dark.svg">
</picture>

**Package Status**

[![Status](https://img.shields.io/badge/Status-Release%20Candidate-blue)](CHANGELOG.md)
[![Version](https://img.shields.io/badge/Version-v1.0.0--rc.2-blue)](https://packagist.org/packages/maatify/php-category#v1.0.0-rc.2)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4.27%20%3C8.5%20%7C%7C%20%3E%3D8.5.12%20%3C9.0-8892BF)](composer.json)
[![License](https://img.shields.io/badge/License-Proprietary-green)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-2F9E44.svg)](CONTRIBUTING.md#local-verification)

**Ecosystem / Adoption**

[![Packagist](https://img.shields.io/badge/Registry-Packagist-orange)](https://packagist.org/packages/maatify/php-category)
[![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/php-category?label=Monthly%20Downloads)](https://packagist.org/packages/maatify/php-category/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/maatify/php-category?label=Total%20Downloads)](https://packagist.org/packages/maatify/php-category/stats)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet)](https://github.com/Maatify-ltd)
[![Install](https://img.shields.io/badge/Install-1.0.0--rc.2-blue)](#installation)

**Documentation**

[![Usage Guide](https://img.shields.io/badge/Usage%20Guide-Read-blue)](docs/guides/USAGE_GUIDE.md)
[![Examples](https://img.shields.io/badge/Examples-View-blue)](examples/)
[![Package Reference](https://img.shields.io/badge/Reference-Read-blue)](CATEGORY_PACKAGE_REFERENCE.md)
[![Changelog](https://img.shields.io/badge/Changelog-View-blue)](CHANGELOG.md)
[![Security Policy](https://img.shields.io/badge/Security-Policy-blue)](SECURITY.md)
[![Contributing Guide](https://img.shields.io/badge/Contributing-Guide-blue)](CONTRIBUTING.md)

Framework-neutral PHP contracts and MySQL storage for hierarchical categories,
content, extensible fields, image roles, and assignments to Host-owned media.

**Current published release candidate** · [v1.0.0-rc.2 on Packagist](https://packagist.org/packages/maatify/php-category#v1.0.0-rc.2)

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

Composer admits PHP `>=8.4.27 <8.5 || >=8.5.12 <9.0`. The CI compatibility
matrix covers Stable PHP 8.4 and 8.5 at or above their approved patch floors.
Later PHP 8 minors have forward Composer eligibility and require separate Stable
qualification before support is claimed.

The package requires `ext-ctype`, `ext-mbstring`, `ext-pdo`, and
`ext-pdo_mysql`. It uses MySQL 8.0.16+ with InnoDB and enforced `CHECK`
constraints. Runtime dependencies are `maatify/exceptions`,
`maatify/shared-common`, and the exact `maatify/php-persistence 1.0.0-rc.1`
trial. See [composer.json](composer.json)
for the declared constraints and [local verification](CONTRIBUTING.md#local-verification)
for the Composer and runtime checks.

## Installation

Install the published RC.2 from Packagist in your Composer project. Keep
`minimum-stability: stable` and explicitly allow the exact Persistence release
candidate required by Category.

```bash
composer require maatify/php-category:1.0.0-rc.2 maatify/php-persistence:1.0.0-rc.1@RC
```

For the previously published RC.1, use its
[tag documentation](https://github.com/Maatify-ltd/php-category/tree/v1.0.0-rc.1)
and [MIT license](https://github.com/Maatify-ltd/php-category/blob/v1.0.0-rc.1/LICENSE).
That artifact has different PHP and Persistence requirements.

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

The RC.2 artifact is proprietary to Maatify Ltd. Obtain written authorization
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
