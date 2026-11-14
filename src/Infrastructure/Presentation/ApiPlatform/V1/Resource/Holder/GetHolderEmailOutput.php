<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

final readonly class GetHolderEmailOutput
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
}
