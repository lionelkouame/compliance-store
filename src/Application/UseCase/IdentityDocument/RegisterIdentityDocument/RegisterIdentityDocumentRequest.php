<?php

declare(strict_types=1);

namespace App\Application\UseCase\RegisterIdentityDocument;

final readonly class RegisterIdentityDocumentCommand
{
    /**
     * @param string $expiresAt date in Y-m-d format
     */
    public function __construct(
        public string $type,
        public string $country,
        public string $number,
        public string $expiresAt,
    ) {}
}
