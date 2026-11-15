<?php

declare(strict_types=1);

namespace Maatify\Category\ImageAssignment\Api\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Public application contract for ImageAssignment business mutations and visible/management reads and lists. */
interface ImageAssignmentApiInterface
{
    /**
     * Assigns a media asset to an exact Category scope and returns the assignment identity.
     * A supplied Role must be active and non-deleted.
     */
    public function assign(CreateCategoryImageAssignmentCommand $command): int;

    /** Repositions a non-deleted assignment within its exact Category, Role, language, and platform scope. */
    public function reorder(UpdateCategoryImageAssignmentDisplayOrderCommand $command): void;

    /** Sets the default assignment for its exact scope, replacing any previous default atomically. */
    public function setDefault(SetCategoryImageAssignmentDefaultCommand $command): void;

    /** Clears the selected assignment’s default flag without promoting another assignment. */
    public function clearDefault(ClearCategoryImageAssignmentDefaultCommand $command): void;

    /**
     * Marks the assignment deleted and clears its default flag while keeping the stable assignment identity.
     *
     * Removes the assignment reversibly; use restore() to make it visible again.
     */
    public function remove(SoftDeleteCategoryImageAssignmentCommand $command): void;

    /** Restores the deleted assignment under its existing identity with its default flag clear. */
    public function restore(RestoreCategoryImageAssignmentCommand $command): void;

    /** Returns visible assignments in the exact language and platform scope under the supplied Role filter. */
    public function listVisibleForCategory(
        int $categoryId,
        CategoryImageAssignmentScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryImageAssignmentCollectionDTO;

    /**
     * Finds the Image Assignment by identity under the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\ImageAssignment\Exception\CategoryImageAssignmentNotFoundException
     */
    public function getByIdForManagement(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageAssignmentDTO;

    /** Lists Image Assignment records under the supplied management criteria in deterministic order. */
    public function listForManagement(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO;

    /**
     * Returns a management page, base total, and filtered count for Image Assignment records under the supplied criteria.
     *
     * @return PageResultDTO<CategoryImageAssignmentDTO>
     */
    public function paginateForManagement(
        CategoryImageAssignmentListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;
}
