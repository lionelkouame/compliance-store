<?php

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetHolderEmailOutput
{
    public function __construct(
        public string $holderId,
        public string $holderName,
        public string $birthDate,
        public string $status,
        #[Assert\Email(message: 'Email is not valid.')]
        public string $email,
        public string $createdAt,
        public ?string $consentCollectedAt,
        public ?string $erasureRequestedAt,
        public ?string $erasureCompletedAt,
    ){}
}
