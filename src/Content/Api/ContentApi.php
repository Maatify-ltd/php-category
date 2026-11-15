<?php

declare(strict_types=1);

namespace Maatify\Category\Content\Api;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Content\Api\Contract\ContentApiInterface;
use Maatify\Category\Content\Contract\ContentServiceInterface;
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

/** Public Category Content domain API. */
final readonly class ContentApi implements ContentApiInterface
{
    public function __construct(private ContentServiceInterface $service) {}

    /** {@inheritDoc} */
    public function create(CreateCategoryContentCommand $command): int
    {
        return $this->service->create($command);
    }

    /** {@inheritDoc} */
    public function update(UpdateCategoryContentCommand $command): void
    {
        $this->service->update($command);
    }

    /** {@inheritDoc} */
    public function updateName(UpdateCategoryContentNameCommand $command): void
    {
        $this->service->updateName($command);
    }

    /** {@inheritDoc} */
    public function updateDescription(UpdateCategoryContentDescriptionCommand $command): void
    {
        $this->service->updateDescription($command);
    }

    /** {@inheritDoc} */
    public function softDelete(SoftDeleteCategoryContentCommand $command): void
    {
        $this->service->softDelete($command);
    }

    /** {@inheritDoc} */
    public function restore(RestoreCategoryContentCommand $command): void
    {
        $this->service->restore($command);
    }

    /** {@inheritDoc} */
    public function listVisibleForCategory(
        int $categoryId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentCollectionDTO {
        return $this->service->listVisibleForCategory($categoryId, $criteria);
    }

    /** {@inheritDoc} */
    public function getByIdForManagement(
        int $contentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentDTO {
        return $this->service->getByIdForManagement($contentId, $deletedState);
    }

    /** {@inheritDoc} */
    public function listForManagement(CategoryContentListCriteriaDTO $criteria): CategoryContentCollectionDTO
    {
        return $this->service->listForManagement($criteria);
    }

    /** {@inheritDoc} */
    public function paginateForManagement(CategoryContentListCriteriaDTO $criteria, PageRequest $pageRequest): PageResultDTO
    {
        return $this->service->paginateForManagement($criteria, $pageRequest);
    }
}
