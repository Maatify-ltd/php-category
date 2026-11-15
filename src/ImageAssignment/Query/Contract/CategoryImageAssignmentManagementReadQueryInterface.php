<?php

declare(strict_types=1);

namespace Maatify\Category\ImageAssignment\Query\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Dedicated management read port for Image Assignments. */
interface CategoryImageAssignmentManagementReadQueryInterface
{
    public function findImageAssignmentById(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageAssignmentDTO;

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO;

    /** @return PageResultDTO<CategoryImageAssignmentDTO> */
    public function paginateImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
        PageRequest $pageRequest,
    ): PageResultDTO;
}
