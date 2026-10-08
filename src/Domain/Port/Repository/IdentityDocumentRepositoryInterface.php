<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\IdentityDocument;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocumentNumber;
use App\Domain\ValueObject\IdentityDocumentType;

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
