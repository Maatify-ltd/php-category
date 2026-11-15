<?php

declare(strict_types=1);

namespace Maatify\Category\ImageAssignment\Contract;

use DateTimeImmutable;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;

/** Write port for the Category-owned Media Asset assignment lifecycle. */
interface CategoryImageAssignmentCommandRepositoryInterface
{
    /** Inserts a record using the supplied mutation time and returns its generated identity. */
    public function create(CreateCategoryImageAssignmentCommand $command, DateTimeImmutable $occurredAt): int;

    /** Moves the row within its current ordering scope. Returns false when the row cannot be repositioned. */
    public function updateDisplayOrder(
        UpdateCategoryImageAssignmentDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Returns false when the target is missing or soft-deleted. */
    public function setDefault(
        SetCategoryImageAssignmentDefaultCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Returns false when the target is missing or soft-deleted. */
    public function clearDefault(
        ClearCategoryImageAssignmentDefaultCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Marks a non-deleted row deleted. Returns false when there is no matching non-deleted row. */
    public function softDelete(
        SoftDeleteCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    /** Clears the deletion marker. Returns false when there is no matching deleted row. */
    public function restore(
        RestoreCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;
}
