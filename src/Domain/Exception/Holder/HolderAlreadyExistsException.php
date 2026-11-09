<?php

declare(strict_types=1);

namespace App\Domain\Exception\Holder;

use App\Domain\Exception\AlreadyExistException;

class HolderAlreadyExistsException extends AlreadyExistException
{
    public function __construct()
    {
        parent::__construct('A holder with string already exists.');
    }
}
