<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\NotFoundException;

final class StorageSpaceNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('No storage space found');
    }
}
