<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Query\Contract;

use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;

/** Read port consumed by Content Field mutation orchestration. */
interface CategoryContentFieldQueryReaderInterface
{
    /** Returns the row regardless of deletion state, or null when the identity is absent. */
    public function findContentFieldById(int $fieldId): ?CategoryContentFieldDTO;

    /** Finds and locks a Category Content Field regardless of soft-delete state. */
    public function findContentFieldByIdForUpdate(int $fieldId): ?CategoryContentFieldDTO;
}
