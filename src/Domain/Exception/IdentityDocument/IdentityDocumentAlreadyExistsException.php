<?php

declare(strict_types=1);

namespace App\Domain\Exception\IdentityDocument;

use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;

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
