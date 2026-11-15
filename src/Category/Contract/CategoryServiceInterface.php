<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Hierarchy\Command\MoveCategoryCommand;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Lifecycle\Command\RestoreCategoryCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Ordering\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/**
 * Public application contract for Category business mutations.
 * Public application contract for visible Category reads and lists.
 * Public application contract for management Category reads.
 */
interface CategoryServiceInterface
{
    /**
     * Creates a Category with a reserved code and returns its assigned identity.
     * A supplied parent must exist and be non-deleted.
     */
    public function create(CreateCategoryCommand $command): int;

    /** Moves a Category within the hierarchy. Rejects missing parents and moves that would create a cycle. */
    public function move(MoveCategoryCommand $command): void;

    /**
     * Marks a Category deleted after checking that it has no non-deleted direct children.
     * Its code remains reserved.
     */
    public function softDelete(SoftDeleteCategoryCommand $command): void;

    /** Restores the deleted Category using its existing identity and code. */
    public function restore(RestoreCategoryCommand $command): void;

    /**
     * Sets the status of a non-deleted Category.
     * Reapplying the current status succeeds without treating the row as missing.
     */
    public function updateStatus(UpdateCategoryStatusCommand $command): void;

    /** Changes the position of a non-deleted Category within its current sibling scope. */
    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command): void;

    /**
     * Returns an active, non-deleted Category whose entire ancestor chain is visible.
     *
     * @throws \Maatify\Category\Exception\CategoryNotFoundException
     */
    public function getById(int $categoryId): CategoryDTO;

    /** Returns a bounded list of visible root Categories in display-order and identity order. */
    public function listRootCategories(
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO;

    /** Returns a bounded list of visible direct children of the selected parent in display-order and identity order. */
    public function listChildren(
        int $parentId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO;

    /**
     * Finds the Category by identity under the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\Exception\CategoryNotFoundException
     */
    public function getByIdForManagement(
        int $categoryId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryDTO;

    /**
     * Finds a Category by its code and the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\Exception\CategoryNotFoundException
     */
    public function getByCode(
        string $code,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryDTO;

    /** Lists Category records under the supplied management criteria in deterministic order. */
    public function listForManagement(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Lists root Categories under the supplied management criteria in display-order and identity order. */
    public function listRootCategoriesForManagement(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Lists direct children under the supplied management criteria in display-order and identity order. */
    public function listChildrenForManagement(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /**
     * Returns a management page, base total, and filtered count for Category records under the supplied criteria.
     *
     * @return PageResultDTO<CategoryDTO>
     */
    public function paginateForManagement(
        CategoryListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;

    /**
     * Returns a management page, base total, and filtered count for root Categories.
     *
     * @return PageResultDTO<CategoryDTO>
     */
    public function paginateRootCategoriesForManagement(
        CategoryListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;

    /**
     * Returns a management page, base total, and filtered count for direct children of the selected parent.
     *
     * @return PageResultDTO<CategoryDTO>
     */
    public function paginateChildrenForManagement(
        int $parentId,
        CategoryListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;
}
