<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\IdentityDocument;

use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocument\ExpiryDate;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;


final readonly class InfosIdentityDocument
{
    public function __construct(
        public IdentityDocumentId $id,
        public IdentityDocumentType $type,
        public CountryCode $country,
        public IdentityDocumentNumber $number,
        public ExpiryDate $expiresAt,
        public \DateTimeImmutable $createdAt,
    ) {}
}
