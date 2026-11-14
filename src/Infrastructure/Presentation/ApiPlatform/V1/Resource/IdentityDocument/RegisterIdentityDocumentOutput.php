<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\IdentityDocument;

final readonly class RegisterIdentityDocumentOutput
{
    public function __construct(
        public string $id,
        public string $type,
        public string $country,
        public string $number,
        public string $expiresAt,
        public string $createdAt,
    ) {}
}
