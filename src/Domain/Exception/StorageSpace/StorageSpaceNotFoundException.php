<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\NotFoundException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;

final class StorageSpaceNotFoundException extends NotFoundException
{
    public function __construct(string $message = 'No storage space found.')
    {
        parent::__construct($message);
    }

    public static function withId(StorageSpaceId $id): self
    {
        return new self(\sprintf('No storage space found with id "%s".', $id->value));
    }
}
