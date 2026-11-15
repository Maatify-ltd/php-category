<?php

declare(strict_types=1);

namespace Maatify\Category\Common\Infrastructure;

use Maatify\Category\Common\Exception\CategoryPersistenceException;
use PDO;

/** Derives the same exact-scope key as the MySQL generated ordering_scope columns. */
final class PdoCategoryOrderingScope
{
    private function __construct() {}

    /** Computes the scope from bound values before locking an insertion range. */
    public static function forValues(
        PDO $pdo,
        int $categoryId,
        ?string $language,
        ?string $platform,
        ?int $roleId = null,
    ): string {
        $statement = $pdo->prepare(
            "SELECT CONCAT('C', CAST(:category_id AS UNSIGNED), '|', COALESCE(CAST(:role_id AS UNSIGNED), 0), '|', "
            . "COALESCE(SHA2(TRIM(TRAILING WEIGHT_STRING(_utf8mb4' ' COLLATE utf8mb4_unicode_ci) "
            . "FROM WEIGHT_STRING(CONVERT(:language USING utf8mb4) COLLATE utf8mb4_unicode_ci)), 256), 'N'), '|', "
            . "COALESCE(SHA2(TRIM(TRAILING WEIGHT_STRING(_utf8mb4' ' COLLATE utf8mb4_unicode_ci) "
            . "FROM WEIGHT_STRING(CONVERT(:platform USING utf8mb4) COLLATE utf8mb4_unicode_ci)), 256), 'N'))",
        );
        $statement->execute([
            'category_id' => $categoryId,
            'role_id' => $roleId,
            'language' => $language,
            'platform' => $platform,
        ]);
        $value = $statement->fetchColumn();
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType('ordering_scope');
        }

        return $value;
    }
}
