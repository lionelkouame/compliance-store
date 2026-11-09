<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\InvalidDataException;

final class InvalidStatusStorageException extends InvalidDataException
{
    public function __construct()
    {
        parent::__construct('The status is not valid');
    }
}
