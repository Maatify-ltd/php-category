<?php

declare(strict_types=1);

namespace Maatify\Category\ImageRole\Lifecycle\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;
use Throwable;

/** The Role key is reserved by an existing record, including a deleted Role. */
final class CategoryImageRoleAlreadyExistsException extends GenericConflictMaatifyException implements CategoryExceptionInterface
{
    public static function withKey(string $roleKey, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Category Image Role with key "%s" already exists.', $roleKey),
            0,
            $previous,
        );
    }
}
