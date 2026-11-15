<?php

declare(strict_types=1);

namespace Maatify\Category\ImageRole\Query\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ImageRole\CategoryImageRoleDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleCollectionDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Dedicated management read port for Image Roles. */
interface CategoryImageRoleManagementReadQueryInterface
{
    /** Returns the matching row in the requested deletion state, or null when no row matches. */
    public function findImageRoleById(
        int $roleId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageRoleDTO;

    /** Returns the matching row in the requested deletion state, or null when no row matches. */
    public function findImageRoleByKey(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageRoleDTO;

    /** Returns bounded records under the explicit management criteria in deterministic order. */
    public function listImageRoles(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO;

    /**
     * Returns the management page, base total, and filtered count for the requested scope.
     *
     * @return PageResultDTO<CategoryImageRoleDTO>
     */
    public function paginateImageRoles(CategoryImageRoleListCriteriaDTO $criteria, PageRequest $pageRequest): PageResultDTO;
}
