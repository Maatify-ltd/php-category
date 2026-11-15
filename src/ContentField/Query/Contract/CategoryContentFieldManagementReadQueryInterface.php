<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Query\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Dedicated management read port for Content Fields. */
interface CategoryContentFieldManagementReadQueryInterface
{
    /** Returns the matching row in the requested deletion state, or null when no row matches. */
    public function findContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentFieldDTO;

    /** Returns bounded records under the explicit management criteria in deterministic order. */
    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO;

    /**
     * Returns the management page, base total, and filtered count for the requested scope.
     *
     * @return PageResultDTO<CategoryContentFieldDTO>
     */
    public function paginateContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;
}
