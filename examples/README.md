# Category examples

[category-workflow.php](category-workflow.php) demonstrates the public Category
facade against MySQL. It creates a hierarchy, adds localized content and a
scoped JSON field, assigns an external image as the exact-scope default, and
compares storefront visibility with management pagination after changing a
Category's status.

From the repository checkout, install the [development prerequisites](../CONTRIBUTING.md)
and run the example with the same database launcher used in CI:

```bash
composer install
bash scripts/ci/with-category-mysql.sh --install-schema php examples/category-workflow.php
```

To use an existing **disposable** MySQL database, apply
[schema/category.sql](../schema/category.sql), set CATEGORY_TEST_DSN,
CATEGORY_TEST_DB_USER, and CATEGORY_TEST_DB_PASSWORD, then run
`php examples/category-workflow.php` from the checkout. The example uses the
production Composer autoloader and rolls back its writes through the Host
transaction. It prints Category example completed. on success.

The [usage guide](../docs/guides/USAGE_GUIDE.md) explains the public contracts
behind each step.
