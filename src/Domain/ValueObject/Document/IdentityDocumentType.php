<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

enum IdentityDocumentType: string
{
    case Passport = 'Passport';
    case NationalCard = 'NationalCard';
}
