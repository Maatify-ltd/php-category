<?php

declare(strict_types=1);

namespace Maatify\Category\ImageAssignment\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/** No assignment matches the operation’s identity and deletion requirements. */
final class CategoryImageAssignmentNotFoundException extends ResourceNotFoundMaatifyException implements CategoryExceptionInterface
{
    public static function withId(int $assignmentId): self
    {
        return new self(sprintf('Category Image Assignment with id %d was not found.', $assignmentId));
    }
}
