<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Mutation\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;
use Throwable;

/** The exact Category, key, language, and platform identity is already reserved. */
final class CategoryContentFieldAlreadyExistsException extends GenericConflictMaatifyException implements CategoryExceptionInterface
{
    public static function withIdentity(
        int $categoryId,
        string $fieldKey,
        ?string $languageCode,
        ?string $platform,
        ?Throwable $previous = null,
    ): self {
        $language = $languageCode === null ? 'NULL language' : sprintf('language "%s"', $languageCode);
        $platformValue = $platform === null ? 'NULL platform' : sprintf('platform "%s"', $platform);

        return new self(
            sprintf(
                'Category Content Field "%s" for Category %d, %s, and %s already exists.',
                $fieldKey,
                $categoryId,
                $language,
                $platformValue,
            ),
            0,
            $previous,
        );
    }
}
