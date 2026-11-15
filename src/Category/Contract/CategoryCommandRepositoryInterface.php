<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Hierarchy\Command\MoveCategoryCommand;
use Maatify\Category\Lifecycle\Command\RestoreCategoryCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Ordering\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;

/**
 * Write port for Category persistence.
 *
 * The Category application receives mutation timestamps from the Host-provided
 * ClockInterface. Persistence adapters only persist the supplied timestamp and
 * delegate display-order mechanics to the approved persistence capability.
 */
interface CategoryCommandRepositoryInterface
{
    /** Inserts a record using the supplied mutation time and returns its generated identity. */
    public function create(CreateCategoryCommand $command, DateTimeImmutable $occurredAt): int;

    /** Returns true when the non-deleted Category is moved or already under the requested parent. */
    public function move(MoveCategoryCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Marks a non-deleted row deleted. Returns false when there is no matching non-deleted row. */
    public function softDelete(SoftDeleteCategoryCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Clears the deletion marker. Returns false when there is no matching deleted row. */
    public function restore(RestoreCategoryCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Writes the status for a non-deleted row. An unchanged row still counts as success. */
    public function updateStatus(UpdateCategoryStatusCommand $command, DateTimeImmutable $occurredAt): bool;

    /** Moves the row within its current ordering scope. Returns false when the row cannot be repositioned. */
    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command, DateTimeImmutable $occurredAt): bool;
}
