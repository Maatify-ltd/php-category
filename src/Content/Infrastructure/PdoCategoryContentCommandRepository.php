<?php

declare(strict_types=1);

namespace Maatify\Category\Content\Infrastructure;

use DateTimeImmutable;
use Maatify\Category\Content\Contract\CategoryContentCommandRepositoryInterface;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\RestoreCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\UpdateCategoryContentCommand;
use Maatify\Category\Common\Exception\CategoryPersistenceException;
use Maatify\Category\Content\Mutation\Exception\CategoryContentAlreadyExistsException;
use PDO;
use PDOException;

/** PDO write adapter for Category Content. */
final readonly class PdoCategoryContentCommandRepository implements CategoryContentCommandRepositoryInterface
{
    private const CONTENT_TABLE = 'maa_category_category_contents';

    public function __construct(private PDO $pdo) {}

    /** {@inheritDoc} */
    public function create(CreateCategoryContentCommand $command, DateTimeImmutable $occurredAt): int
    {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO `' . self::CONTENT_TABLE . '` '
                . '(`category_id`, `language_code`, `name`, `description`, '
                . '`created_at`, `updated_at`, `deleted_at`) '
                . 'VALUES (:category_id, :language_code, :name, :description, '
                . ':created_at, :updated_at, NULL)',
            );
            $timestamp = $this->formatTimestamp($occurredAt);
            $statement->execute([
                'category_id' => $command->categoryId,
                'language_code' => $command->languageCode,
                'name' => $command->name,
                'description' => $command->description,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        } catch (PDOException $exception) {
            $driverCode = $exception->errorInfo[1] ?? null;
            if ((is_int($driverCode) || is_string($driverCode)) && (int) $driverCode === 1062) {
                throw CategoryContentAlreadyExistsException::withIdentity(
                    $command->categoryId,
                    $command->languageCode,
                    $exception,
                );
            }

            throw $exception;
        }

        $id = $this->pdo->lastInsertId();
        if ($id === false || !ctype_digit($id) || (int) $id < 1) {
            throw CategoryPersistenceException::invalidContentAutoIncrementIdentity();
        }

        return (int) $id;
    }

    /** {@inheritDoc} */
    public function update(UpdateCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CONTENT_TABLE . '` '
            . 'SET `name` = :name, `description` = :description, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $statement->execute([
            'name' => $command->name,
            'description' => $command->description,
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->contentId,
        ]);

        if ($statement->rowCount() > 0) {
            return true;
        }

        // Use a current read because a no-op UPDATE can match a row newer
        // than the Host transaction's consistent-read snapshot.
        $exists = $this->pdo->prepare(
            'SELECT 1 FROM `' . self::CONTENT_TABLE . '` '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL FOR UPDATE',
        );
        $exists->execute(['id' => $command->contentId]);

        return $exists->fetchColumn() !== false;
    }

    /** {@inheritDoc} */
    public function softDelete(
        SoftDeleteCategoryContentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CONTENT_TABLE . '` '
            . 'SET `deleted_at` = :deleted_at, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $timestamp = $this->formatTimestamp($occurredAt);
        $statement->execute([
            'deleted_at' => $timestamp,
            'updated_at' => $timestamp,
            'id' => $command->contentId,
        ]);

        return $statement->rowCount() > 0;
    }

    /** {@inheritDoc} */
    public function restore(
        RestoreCategoryContentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::CONTENT_TABLE . '` '
            . 'SET `deleted_at` = NULL, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NOT NULL',
        );
        $statement->execute([
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->contentId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function formatTimestamp(DateTimeImmutable $occurredAt): string
    {
        return $occurredAt->format('Y-m-d H:i:s');
    }
}
