<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\Holder;

use App\Domain\Entity\Holder;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use App\Domain\ValueObject\Shared\Generic\BirthDate;
use App\Domain\ValueObject\Shared\Identifier\HolderId;

final readonly class InfosHolder
{
    public function __construct(
        public HolderId $id,
        public HolderName $name,
        public BirthDate $birthDate,
        public HolderStatus $status,
        public Email $email,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $consentCollectedAt,
        public ?\DateTimeImmutable $erasureRequestedAt,
        public ?\DateTimeImmutable $anonymizedAt,
    ) {}

    /**
     * Construit le read model à partir de l'agrégat, pour que chaque use case
     * de lecture n'ait pas à recopier ce mapping.
     */
    public static function fromEntity(Holder $holder): self
    {
        return new self(
            id: $holder->id(),
            name: $holder->name(),
            birthDate: $holder->birthDate(),
            status: $holder->status(),
            email: $holder->email(),
            createdAt: $holder->createdAt(),
            consentCollectedAt: $holder->consentCollectedAt(),
            erasureRequestedAt: $holder->erasureRequestedAt(),
            anonymizedAt: $holder->anonymizedAt(),
        );
    }
}
