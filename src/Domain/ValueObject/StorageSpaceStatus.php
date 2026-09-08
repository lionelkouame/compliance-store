<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum StorageSpaceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
