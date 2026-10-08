<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\IdentityDocumentNumber;
use App\Domain\ValueObject\IdentityDocumentType;

final class IdentityDocumentAlreadyExistsException extends \DomainException
{
    public function __construct(IdentityDocumentType $type, CountryCode $country, IdentityDocumentNumber $number)
    {
        parent::__construct(\sprintf(
            'A %s issued by %s with number "%s" already exists.',
            $type->value,
            $country->value,
            $number->value,
        ));
    }
}
