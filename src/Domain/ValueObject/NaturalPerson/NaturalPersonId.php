<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\NaturalPerson;

use App\Domain\ValueObject\IdentifierInterface;
use App\Domain\ValueObject\IdentifierTrait;

final readonly class NaturalPersonId implements IdentifierInterface
{
    use IdentifierTrait;
}
