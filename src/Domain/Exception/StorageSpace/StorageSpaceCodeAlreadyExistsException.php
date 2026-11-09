<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;

final class StorageSpaceCodeAlreadyExistsException extends \DomainException
{
    public function __construct(StorageSpaceCode $code)
    {
        parent::__construct(\sprintf('A storage space with code "%s" already exists.', $code->value));
    }
}
