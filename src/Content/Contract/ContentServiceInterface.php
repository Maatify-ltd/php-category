<?php

declare(strict_types=1);

namespace Maatify\Category\Content\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\RestoreCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\UpdateCategoryContentCommand;
use Maatify\Category\Content\Mutation\Command\UpdateCategoryContentDescriptionCommand;
use Maatify\Category\Content\Mutation\Command\UpdateCategoryContentNameCommand;
use Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Public application contract for Content business mutations and visible/management reads and lists. */
interface ContentServiceInterface
{
    /**
     * Creates a Content row for a non-deleted Category and returns its identity.
     * A nullable language selects the exact localization slot.
     */
    public function create(CreateCategoryContentCommand $command): int;

    /** Updates the mutable fields of a non-deleted Content row while retaining its Category and language identity. */
    public function update(UpdateCategoryContentCommand $command): void;

    /** Updates the name on a non-deleted Content row, preserving its description and exact localization. */
    public function updateName(UpdateCategoryContentNameCommand $command): void;

    /** Updates the description on a non-deleted Content row, preserving its name and exact localization. */
    public function updateDescription(UpdateCategoryContentDescriptionCommand $command): void;

    /** Marks the Content row deleted while retaining its Category and language identity. */
    public function softDelete(SoftDeleteCategoryContentCommand $command): void;

    /** Restores the deleted Content row using its existing localization identity. */
    public function restore(RestoreCategoryContentCommand $command): void;

    /** Returns visible Content for the Category. The package does not choose a fallback translation. */
    public function listVisibleForCategory(
        int $categoryId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentCollectionDTO;

    /**
     * Finds the Content row by identity under the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\Content\Exception\CategoryContentNotFoundException
     */
    public function getByIdForManagement(
        int $contentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentDTO;

    /** Lists Content row records under the supplied management criteria in deterministic order. */
    public function listForManagement(CategoryContentListCriteriaDTO $criteria): CategoryContentCollectionDTO;

    /**
     * Returns a management page, base total, and filtered count for Content row records under the supplied criteria.
     *
     * @return PageResultDTO<CategoryContentDTO>
     */
    public function paginateForManagement(CategoryContentListCriteriaDTO $criteria, PageRequest $pageRequest): PageResultDTO;
}
