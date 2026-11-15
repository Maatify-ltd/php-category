<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Contract;

use DateTimeImmutable;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;

/** Write port for the complete Category Content Field lifecycle. */
interface CategoryContentFieldCommandRepositoryInterface
{
    /** Inserts a record using the supplied mutation time and returns its generated identity. */
    public function create(CreateCategoryContentFieldCommand $command, DateTimeImmutable $occurredAt): int;

    /** Writes the mutable data for a non-deleted row. An unchanged row still counts as success. */
    public function update(UpdateCategoryContentFieldCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Moves the row within its current ordering scope. Returns false when the row cannot be repositioned. */
    public function updateDisplayOrder(
        UpdateCategoryContentFieldDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Marks a non-deleted row deleted. Returns false when there is no matching non-deleted row. */
    public function softDelete(
        SoftDeleteCategoryContentFieldCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Clears the deletion marker. Returns false when there is no matching deleted row. */
    public function restore(
        RestoreCategoryContentFieldCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;
}
