<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\IdentityDocumentExpiredException;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocumentNumber;
use App\Domain\ValueObject\IdentityDocumentType;

/**
 * An identity document (passport, national ID card) issued by a country.
 * Only a document still valid at registration time can be registered.
 */
final readonly class IdentityDocument
{
    private function __construct(
        private IdentityDocumentId $id,
        private IdentityDocumentType $type,
        private CountryCode $country,
        private IdentityDocumentNumber $number,
        private \DateTimeImmutable $expiresAt,
        private \DateTimeImmutable $createdAt,
    ) {}

    /**
     * @throws IdentityDocumentExpiredException when $expiresAt is before today (UTC)
     */
    public static function create(
        IdentityDocumentId $id,
        IdentityDocumentType $type,
        CountryCode $country,
        IdentityDocumentNumber $number,
        \DateTimeImmutable $expiresAt,
    ): self {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // A document is valid through its whole expiry day.
        if ($expiresAt->format('Y-m-d') < $now->format('Y-m-d')) {
            throw new IdentityDocumentExpiredException($expiresAt);
        }

        return new self(
            id: $id,
            type: $type,
            country: $country,
            number: $number,
            expiresAt: $expiresAt,
            createdAt: $now,
        );
    }

    public function id(): IdentityDocumentId
    {
        return $this->id;
    }

    public function type(): IdentityDocumentType
    {
        return $this->type;
    }

    public function country(): CountryCode
    {
        return $this->country;
    }

    public function number(): IdentityDocumentNumber
    {
        return $this->number;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
