<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\IdentityDocument;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;

interface IdentityDocumentRepositoryInterface
{
    public function add(IdentityDocument $identityDocument): void;

    public function get(IdentityDocumentId $id): ?IdentityDocument;

    public function findByNumber(
        IdentityDocumentType $type,
        CountryCode $country,
        IdentityDocumentNumber $number,
    ): ?IdentityDocument;
}
