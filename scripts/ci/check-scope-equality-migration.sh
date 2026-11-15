#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$project_root"

if [[ -z "${CATEGORY_TEST_COMPOSE_PROJECT:-}" ]]; then
    if [[ ! -S "${CATEGORY_MIGRATION_MYSQL_SOCKET:-}" || ! "${CATEGORY_MIGRATION_MYSQL_DATABASE:-}" =~ ^category_migration_[a-z0-9_]+$ ]]; then
        printf 'Run with scripts/ci/with-category-mysql.sh or use an empty disposable category_migration_* native schema.\n' >&2
        exit 2
    fi
    : "${CATEGORY_TEST_DB_USER:?Set the native disposable MySQL user}"
    : "${CATEGORY_TEST_DB_PASSWORD:?Set the native disposable MySQL password}"
fi

mysql_batch() {
    if [[ -n "${CATEGORY_TEST_COMPOSE_PROJECT:-}" ]]; then
        docker compose --file tests/compose.integration.yaml --project-name "$CATEGORY_TEST_COMPOSE_PROJECT" \
            exec -T mysql sh -ec \
            'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql --protocol=TCP -h 127.0.0.1 -u "$MYSQL_USER" -D "$MYSQL_DATABASE" --batch --skip-column-names "$@"' \
            sh "$@"
    else
        MYSQL_PWD="$CATEGORY_TEST_DB_PASSWORD" mysql --no-defaults --protocol=SOCKET \
            --socket="$CATEGORY_MIGRATION_MYSQL_SOCKET" --user="$CATEGORY_TEST_DB_USER" \
            --database="$CATEGORY_MIGRATION_MYSQL_DATABASE" --batch --skip-column-names "$@"
    fi
}

verify() {
    if ! "$@"; then
        printf 'Scope migration verification failed: %s\n' "$*" >&2
        exit 1
    fi
}

version="$(mysql_batch -e 'SELECT VERSION()')"
printf 'Scope migration MySQL server: %s\n' "$version"
if [[ -n "${CATEGORY_MIGRATION_EXPECTED_MYSQL_VERSION:-}" ]]; then
    verify test "$version" = "$CATEGORY_MIGRATION_EXPECTED_MYSQL_VERSION"
fi
verify test "$(mysql_batch -e 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')" -eq 0

scratch="$(mktemp -d)"
trap 'rm -rf -- "$scratch"' EXIT
migration=schema/migrations/001-scope-equality.sql
legacy_schema=tests/Fixtures/Migration/category-1.0.0-rc.1.sql

affected_schema() {
    mysql_batch <<'SQL'
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLLATION_NAME, GENERATION_EXPRESSION
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('maa_category_category_image_assignments', 'maa_category_category_content_fields')
ORDER BY TABLE_NAME, ORDINAL_POSITION;
SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('maa_category_category_image_assignments', 'maa_category_category_content_fields')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
SQL
}

business_rows() {
    mysql_batch <<'SQL'
SELECT id, parent_id, code, status, display_order, created_at, updated_at, deleted_at
FROM maa_category_categories ORDER BY id;
SELECT id, category_id, media_asset_id, role_id, HEX(language_code), HEX(platform), is_default,
       display_order, created_at, updated_at, deleted_at
FROM maa_category_category_image_assignments ORDER BY id;
SELECT id, category_id, field_key, HEX(language_code), HEX(platform), format, HEX(value),
       display_order, created_at, updated_at, deleted_at
FROM maa_category_category_content_fields ORDER BY id;
SQL
}

mysql_batch < schema/category.sql
affected_schema > "$scratch/canonical.tsv"

mysql_batch <<'SQL'
DROP TABLE maa_category_category_image_assignments, maa_category_category_content_fields,
           maa_category_category_image_roles, maa_category_category_contents, maa_category_categories;
SQL

mysql_batch < "$legacy_schema"
mysql_batch <<'SQL'
INSERT INTO maa_category_categories (id, code, status, display_order, created_at, updated_at)
VALUES (10, 'migration-fixture', 'active', 1, '2026-01-01', '2026-01-02');
INSERT INTO maa_category_category_image_assignments
(id, category_id, media_asset_id, language_code, platform, is_default, display_order, created_at, updated_at, deleted_at)
VALUES (11, 10, 201, 'en', 'web', 1, 1, '2026-01-01', '2026-01-02', NULL),
       (12, 10, 202, 'en ', 'web', 1, 1, '2026-01-01', '2026-01-02', NULL),
       (13, 10, 203, 'en', 'web', 0, 1, '2026-01-01', '2026-01-02', NULL),
       (14, 10, 204, 'en ', 'web', 0, 1, '2026-01-01', '2026-01-02', '2026-01-03');
INSERT INTO maa_category_category_content_fields
(id, category_id, field_key, language_code, platform, format, value, display_order, created_at, updated_at, deleted_at)
VALUES (21, 10, 'title', 'en', 'web', 'text', 'Title', 1, '2026-01-01', '2026-01-02', NULL),
       (22, 10, 'name', 'en', 'web ', 'text', 'Name', 1, '2026-01-01', '2026-01-02', NULL),
       (23, 10, 'label', 'en', 'web', 'text', 'Label', 1, '2026-01-01', '2026-01-02', NULL),
       (24, 10, 'deleted', 'en', 'web ', 'text', 'Retained', 1, '2026-01-01', '2026-01-02', '2026-01-03');
SQL

affected_schema > "$scratch/legacy-before.tsv"
business_rows > "$scratch/conflicts-before.tsv"
if mysql_batch < "$migration" > "$scratch/conflict.log" 2>&1; then
    printf 'The migration accepted conflicting legacy data.\n' >&2
    exit 1
fi
for diagnostic in 'image defaults' 'image display order' 'content field display order' 'category_scope_migration_requires_zero_conflicts'; do
    verify grep -Fq "$diagnostic" "$scratch/conflict.log"
done
affected_schema > "$scratch/legacy-after.tsv"
business_rows > "$scratch/conflicts-after.tsv"
diff -u "$scratch/legacy-before.tsv" "$scratch/legacy-after.tsv"
diff -u "$scratch/conflicts-before.tsv" "$scratch/conflicts-after.tsv"
printf 'Conflict refusal PASS: all three diagnostics, legacy schema and rows unchanged.\n'

mysql_batch <<'SQL'
UPDATE maa_category_category_image_assignments SET is_default = 0, display_order = 2 WHERE id = 12;
UPDATE maa_category_category_content_fields SET display_order = 2 WHERE id = 22;
SQL
business_rows > "$scratch/resolved-before.tsv"

# Simulate an interruption between the two MySQL DDL statements.
awk '/^ALTER TABLE `maa_category_category_content_fields`/{exit} {print}' "$migration" | mysql_batch >/dev/null
partial_columns="$(mysql_batch -e "SELECT GROUP_CONCAT(COLUMN_TYPE ORDER BY TABLE_NAME) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('maa_category_category_image_assignments', 'maa_category_category_content_fields') AND COLUMN_NAME = 'ordering_scope'")"
verify test "$partial_columns" = 'varchar(512),varchar(192)'

mysql_batch < "$migration" >/dev/null
business_rows > "$scratch/upgraded.tsv"
affected_schema > "$scratch/upgraded-schema.tsv"
diff -u "$scratch/resolved-before.tsv" "$scratch/upgraded.tsv"
diff -u "$scratch/canonical.tsv" "$scratch/upgraded-schema.tsv"

mysql_batch < "$migration" >/dev/null
business_rows > "$scratch/replayed.tsv"
affected_schema > "$scratch/replayed-schema.tsv"
diff -u "$scratch/upgraded.tsv" "$scratch/replayed.tsv"
diff -u "$scratch/canonical.tsv" "$scratch/replayed-schema.tsv"
printf 'Migration PASS: partial retry, replay, business-row retention and canonical column/index parity.\n'
