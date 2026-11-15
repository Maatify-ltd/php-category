<?php

declare(strict_types=1);

namespace Maatify\Category\Lifecycle\Enum;

/** Stored Category visibility status, independent of soft deletion. */
enum CategoryStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
