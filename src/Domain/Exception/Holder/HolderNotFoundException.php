<?php

declare(strict_types=1);

namespace App\Domain\Exception\Holder;

use App\Domain\Exception\NotFoundException;

final class HolderNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Holder not found');
    }
}
