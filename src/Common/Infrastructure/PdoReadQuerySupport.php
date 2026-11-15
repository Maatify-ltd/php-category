<?php

declare(strict_types=1);

namespace Maatify\Category\Common\Infrastructure;

use DateTimeImmutable;
use Maatify\Category\Common\Exception\CategoryPersistenceException;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;
use PDOStatement;

/** Shared scalar, timestamp, and bounded-statement handling for PDO read adapters. */
abstract readonly class PdoReadQuerySupport
{
    /**
     * Executes a prepared read using an integer-bound result limit.
     *
     * @param array<string, int|string> $params
     */
    protected function executeBounded(PDOStatement $statement, array $params, int $maxResults): void
    {
        foreach ($params as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR,
            );
        }
        $statement->bindValue(':max_results', $maxResults, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Reads the integer value of a column returned by PDO as an integer or string.
     *
     * @param array<string, mixed> $row
     */
    protected function integerValue(array $row, string $column): int
    {
        $value = $row[$column] ?? null;
        if (!is_int($value) && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return (int) $value;
    }

    /**
     * Requires an actual non-null string from PDO.
     *
     * @param array<string, mixed> $row
     */
    protected function stringValue(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return $value;
    }

    /**
     * Returns null for null or absent columns and rejects other non-string values.
     *
     * @param array<string, mixed> $row
     */
    protected function nullableStringValue(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;
        if ($value !== null && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return $value;
    }

    /**
     * Returns null for null or absent columns and converts integer or string values.
     *
     * @param array<string, mixed> $row
     */
    protected function nullableIntegerValue(array $row, string $column): ?int
    {
        $value = $row[$column] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_int($value) && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return (int) $value;
    }

    /**
     * Accepts only the SQL 0/1 representations returned as integers or strings.
     *
     * @param array<string, mixed> $row
     */
    protected function booleanValue(array $row, string $column): bool
    {
        $value = $row[$column] ?? null;
        if ($value === 0 || $value === '0') {
            return false;
        }
        if ($value === 1 || $value === '1') {
            return true;
        }

        throw CategoryPersistenceException::unexpectedColumnType($column);
    }

    /**
     * Interprets a stored timestamp in the Host Clock timezone.
     *
     * @param array<string, mixed> $row
     */
    protected function timestampValue(array $row, string $column, ClockInterface $clock): DateTimeImmutable
    {
        $value = $this->stringValue($row, $column);

        try {
            return new DateTimeImmutable($value, $clock->getTimezone());
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }

    /**
     * Interprets a present timestamp in the Host Clock timezone. Null or absent columns return null.
     *
     * @param array<string, mixed> $row
     */
    protected function nullableTimestampValue(
        array $row,
        string $column,
        ClockInterface $clock,
    ): ?DateTimeImmutable {
        $value = $row[$column] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        try {
            return new DateTimeImmutable($value, $clock->getTimezone());
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }
}
