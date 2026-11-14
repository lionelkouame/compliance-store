<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\InvalidDataException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;

final class InvalidStorageSpaceStatusTransitionException extends InvalidDataException
{
    public function __construct(StorageSpaceStatus $from, StorageSpaceStatus $to)
    {
        parent::__construct(\sprintf(
            'Cannot transition a storage space from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
