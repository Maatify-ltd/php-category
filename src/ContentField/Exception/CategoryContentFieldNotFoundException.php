<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/** No Content Field matches the operation’s identity and deletion requirements. */
final class CategoryContentFieldNotFoundException extends ResourceNotFoundMaatifyException implements CategoryExceptionInterface
{
    public static function withId(int $fieldId): self
    {
        return new self(sprintf('Category Content Field with id %d was not found.', $fieldId));
    }
}
