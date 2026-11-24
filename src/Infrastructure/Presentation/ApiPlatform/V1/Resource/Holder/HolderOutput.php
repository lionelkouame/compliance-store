<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use App\Domain\ReadModel\Holder\InfosHolder;

/**
 * Représentation d'un titulaire, partagée par la lecture par identifiant et
 * par la recherche par email.
 */
final readonly class HolderOutput
{
    public function __construct(
        public string $holderId,
        public string $holderName,
        public string $birthDate,
        public string $status,
        public string $email,
        public string $createdAt,
        public ?string $consentCollectedAt,
        public ?string $erasureRequestedAt,
        public ?string $anonymizedAt,
    ) {}

    public static function fromReadModel(InfosHolder $readModel): self
    {
        return new self(
            holderId: $readModel->id->value(),
            holderName: $readModel->name->value,
            birthDate: (string) $readModel->birthDate,
            status: $readModel->status->value,
            email: $readModel->email->value,
            createdAt: $readModel->createdAt->format(\DateTimeInterface::ATOM),
            consentCollectedAt: $readModel->consentCollectedAt?->format(\DateTimeInterface::ATOM),
            erasureRequestedAt: $readModel->erasureRequestedAt?->format(\DateTimeInterface::ATOM),
            anonymizedAt: $readModel->anonymizedAt?->format(\DateTimeInterface::ATOM),
        );
    }
}
