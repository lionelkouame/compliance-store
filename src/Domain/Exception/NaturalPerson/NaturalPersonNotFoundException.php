<?php

declare(strict_types=1);

namespace App\Domain\Exception\NaturalPerson;

use App\Domain\Exception\NotFoundException;

final class NaturalPersonNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Natural person not found');
    }
}
