<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ContentField\CategoryContentFieldScopeDTO;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\UpdateCategoryContentFieldValueCommand;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Public application contract for ContentField business mutations and visible/management reads and lists. */
interface ContentFieldServiceInterface
{
    /** Creates a Content Field in its exact Category, key, language, and platform scope and returns its identity. */
    public function create(CreateCategoryContentFieldCommand $command): int;

    /** Updates the mutable payload of a non-deleted Content Field without replacing its stable scope or key. */
    public function update(UpdateCategoryContentFieldCommand $command): void;

    /** Updates a non-deleted field value with validation against the stored format. */
    public function updateValue(UpdateCategoryContentFieldValueCommand $command): void;

    /** Repositions a non-deleted field within its exact Category, language, and platform scope. */
    public function updateDisplayOrder(UpdateCategoryContentFieldDisplayOrderCommand $command): void;

    /** Marks a field deleted while keeping its stable key and scope reserved. */
    public function softDelete(SoftDeleteCategoryContentFieldCommand $command): void;

    /** Restores the deleted field using its existing identity. */
    public function restore(RestoreCategoryContentFieldCommand $command): void;

    /** Returns visible fields in the exact nullable language and platform scope, ordered by display order and identity. */
    public function listVisibleForCategory(
        int $categoryId,
        CategoryContentFieldScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentFieldCollectionDTO;

    /**
     * Finds the Content Field by identity under the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\ContentField\Exception\CategoryContentFieldNotFoundException
     */
    public function getByIdForManagement(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentFieldDTO;

    /** Lists Content Field records under the supplied management criteria in deterministic order. */
    public function listForManagement(CategoryContentFieldListCriteriaDTO $criteria): CategoryContentFieldCollectionDTO;

    /**
     * Returns a management page, base total, and filtered count for Content Field records under the supplied criteria.
     *
     * @return PageResultDTO<CategoryContentFieldDTO>
     */
    public function paginateForManagement(
        CategoryContentFieldListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;
}
