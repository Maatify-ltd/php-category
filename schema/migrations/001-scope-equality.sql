-- Upgrade legacy length-prefixed keys to collation-equivalent keys.
-- Quiesce writers first. Run this file in MySQL batch mode without --force.
-- The temporary tables disappear with the migration session. See schema/README.md.
CREATE TEMPORARY TABLE category_scope_migration_conflicts (
    issue VARCHAR(40) NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NULL,
    language_code VARCHAR(16) CHARACTER SET utf8mb4 NULL,
    platform VARCHAR(255) CHARACTER SET utf8mb4 NULL,
    display_order INT NULL,
    first_id BIGINT UNSIGNED NOT NULL,
    last_id BIGINT UNSIGNED NOT NULL,
    conflicting_rows BIGINT UNSIGNED NOT NULL
);

INSERT INTO category_scope_migration_conflicts
SELECT 'image defaults', category_id, role_id, language_code, platform,
       NULL, MIN(id), MAX(id), COUNT(*)
FROM maa_category_category_image_assignments
WHERE deleted_at IS NULL AND is_default = 1
GROUP BY category_id, role_id, language_code, platform
HAVING COUNT(*) > 1;

INSERT INTO category_scope_migration_conflicts
SELECT 'image display order', category_id, role_id, language_code, platform,
       display_order, MIN(id), MAX(id), COUNT(*)
FROM maa_category_category_image_assignments
WHERE deleted_at IS NULL
GROUP BY category_id, role_id, language_code, platform, display_order
HAVING COUNT(DISTINCT ordering_scope) > 1;

INSERT INTO category_scope_migration_conflicts
SELECT 'content field display order', category_id, NULL, language_code, platform,
       display_order, MIN(id), MAX(id), COUNT(*)
FROM maa_category_category_content_fields
WHERE deleted_at IS NULL
GROUP BY category_id, language_code, platform, display_order
HAVING COUNT(DISTINCT ordering_scope) > 1;

SELECT * FROM category_scope_migration_conflicts
ORDER BY issue, category_id, first_id;

CREATE TEMPORARY TABLE category_scope_migration_guard (
    conflict_count BIGINT UNSIGNED NOT NULL,
    CONSTRAINT category_scope_migration_requires_zero_conflicts CHECK (conflict_count = 0)
);

-- Batch mode stops on this constraint before either permanent table changes.
INSERT INTO category_scope_migration_guard
SELECT COUNT(*) FROM category_scope_migration_conflicts;
DROP TEMPORARY TABLE category_scope_migration_guard, category_scope_migration_conflicts;


ALTER TABLE `maa_category_category_image_assignments`
    MODIFY COLUMN `language_code_identity` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        COALESCE(SHA2(TRIM(TRAILING WEIGHT_STRING(_utf8mb4' ' COLLATE utf8mb4_unicode_ci) FROM WEIGHT_STRING(`language_code` COLLATE utf8mb4_unicode_ci)), 256), 'N')
    ) STORED COMMENT 'Collation-equivalent language identity; NULL is identified separately',
    MODIFY COLUMN `platform_identity`      VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        COALESCE(SHA2(TRIM(TRAILING WEIGHT_STRING(_utf8mb4' ' COLLATE utf8mb4_unicode_ci) FROM WEIGHT_STRING(`platform` COLLATE utf8mb4_unicode_ci)), 256), 'N')
    ) STORED COMMENT 'Collation-equivalent platform identity; NULL is identified separately',
    MODIFY COLUMN `ordering_scope`         VARCHAR(192) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        CONCAT('C', `category_id`, '|', COALESCE(`role_id`, 0), '|', `language_code_identity`, '|', `platform_identity`)
    ) STORED COMMENT 'Generated Category scope shared by ordering and collation-equivalent reads',
    MODIFY COLUMN `default_scope_identity` VARCHAR(192) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        CASE
            WHEN `is_default` = 1 AND `deleted_at` IS NULL THEN `ordering_scope`
            ELSE NULL
        END
    ) STORED COMMENT 'Conditional generated exact-scope identity; only an active default row participates in the unique key';

ALTER TABLE `maa_category_category_content_fields`
    MODIFY COLUMN `language_code_identity` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        COALESCE(SHA2(TRIM(TRAILING WEIGHT_STRING(_utf8mb4' ' COLLATE utf8mb4_unicode_ci) FROM WEIGHT_STRING(`language_code` COLLATE utf8mb4_unicode_ci)), 256), 'N')
    ) STORED COMMENT 'Collation-equivalent language identity; NULL is identified separately',
    MODIFY COLUMN `platform_identity`      VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        COALESCE(SHA2(TRIM(TRAILING WEIGHT_STRING(_utf8mb4' ' COLLATE utf8mb4_unicode_ci) FROM WEIGHT_STRING(`platform` COLLATE utf8mb4_unicode_ci)), 256), 'N')
    ) STORED COMMENT 'Collation-equivalent platform identity; NULL is identified separately',
    MODIFY COLUMN `ordering_scope`         VARCHAR(192) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (
        CONCAT('C', `category_id`, '|', 0, '|', `language_code_identity`, '|', `platform_identity`)
    ) STORED COMMENT 'Generated Category scope shared by ordering and collation-equivalent reads';
