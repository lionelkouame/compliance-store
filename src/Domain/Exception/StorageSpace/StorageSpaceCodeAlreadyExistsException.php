<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\AlreadyExistException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;

final class StorageSpaceCodeAlreadyExistsException extends AlreadyExistException
{
    public function __construct()
    {
        parent::__construct('A storage space with code already exists');
    }
}
