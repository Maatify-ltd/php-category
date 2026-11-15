<?php

declare(strict_types=1);

namespace Maatify\Category\ImageRole\Api\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ImageRole\CategoryImageRoleDTO;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleCollectionDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\PhpPersistence\Pagination\DTO\PageResultDTO;

/** Public application contract for ImageRole business mutations and management reads. */
interface ImageRoleApiInterface
{
    /**
     * Registers a Role with a globally unique key and returns its identity.
     * Deleted Roles continue to reserve their keys.
     */
    public function create(CreateCategoryImageRoleCommand $command): int;

    /** Sets the status of a non-deleted Role independently of soft deletion. */
    public function updateStatus(UpdateCategoryImageRoleStatusCommand $command): void;

    /** Marks the Role deleted while keeping its key reserved. */
    public function softDelete(SoftDeleteCategoryImageRoleCommand $command): void;

    /** Restores a deleted Role with its existing identity and status. */
    public function restore(RestoreCategoryImageRoleCommand $command): void;

    /**
     * Finds the Image Role by identity under the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\ImageRole\Exception\CategoryImageRoleNotFoundException
     */
    public function getByIdForManagement(
        int $roleId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO;

    /**
     * Finds a Role by its exact key and the requested deletion state, independently of consumer visibility.
     *
     * @throws \Maatify\Category\ImageRole\Exception\CategoryImageRoleNotFoundException
     */
    public function getByKeyForManagement(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO;

    /** Lists Image Role records under the supplied management criteria in deterministic order. */
    public function listForManagement(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO;

    /**
     * Returns a management page, base total, and filtered count for Image Role records under the supplied criteria.
     *
     * @return PageResultDTO<CategoryImageRoleDTO>
     */
    public function paginateForManagement(CategoryImageRoleListCriteriaDTO $criteria, PageRequest $pageRequest): PageResultDTO;
}
