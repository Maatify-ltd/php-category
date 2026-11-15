<?php

declare(strict_types=1);

namespace Maatify\Category\Content\Contract;

use DateTimeImmutable;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\RestoreCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\UpdateCategoryContentCommand;

/** Write port for the complete Category Content mutation lifecycle. */
interface CategoryContentCommandRepositoryInterface
{
    /** Inserts a record using the supplied mutation time and returns its generated identity. */
    public function create(CreateCategoryContentCommand $command, DateTimeImmutable $occurredAt): int;

    /** Writes the mutable data for a non-deleted row. An unchanged row still counts as success. */
    public function update(UpdateCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Marks a non-deleted row deleted. Returns false when there is no matching non-deleted row. */
    public function softDelete(SoftDeleteCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Clears the deletion marker. Returns false when there is no matching deleted row. */
    public function restore(RestoreCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool;
}
