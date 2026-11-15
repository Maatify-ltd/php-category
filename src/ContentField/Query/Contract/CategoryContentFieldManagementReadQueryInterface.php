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
    public function findContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentFieldDTO;

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO;

    /** @return PageResultDTO<CategoryContentFieldDTO> */
    public function paginateContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;
}
