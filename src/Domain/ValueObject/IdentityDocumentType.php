<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum IdentityDocumentType: string
{
    case Passport = 'Passport';
    case NationalCard = 'NationalCard';
}
