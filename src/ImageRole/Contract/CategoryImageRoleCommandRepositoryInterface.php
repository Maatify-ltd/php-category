<?php

declare(strict_types=1);

namespace Maatify\Category\ImageRole\Contract;

use DateTimeImmutable;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\UpdateCategoryImageRoleStatusCommand;

/** Write port for the package-owned Category Image Role lifecycle. */
interface CategoryImageRoleCommandRepositoryInterface
{
    /** Inserts a record using the supplied mutation time and returns its generated identity. */
    public function create(CreateCategoryImageRoleCommand $command, DateTimeImmutable $occurredAt): int;

    /** Writes the status for a non-deleted row. An unchanged row still counts as success. */
    public function updateStatus(
        UpdateCategoryImageRoleStatusCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Marks a non-deleted row deleted. Returns false when there is no matching non-deleted row. */
    public function softDelete(
        SoftDeleteCategoryImageRoleCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Clears the deletion marker. Returns false when there is no matching deleted row. */
    public function restore(
        RestoreCategoryImageRoleCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;
}
