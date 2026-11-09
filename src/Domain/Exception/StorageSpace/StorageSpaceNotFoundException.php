<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\ValueObject\StorageSpace\StorageSpaceId;

final class StorageSpaceNotFoundException extends \DomainException
{
    public function __construct(StorageSpaceId $id)
    {
        parent::__construct(\sprintf('No storage space found with id "%s".', $id->value));
    }
}
