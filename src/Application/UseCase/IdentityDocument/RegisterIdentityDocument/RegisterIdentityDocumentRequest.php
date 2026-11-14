<?php

declare(strict_types=1);

namespace App\Application\UseCase\IdentityDocument\RegisterIdentityDocument;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class RegisterIdentityDocumentRequest implements UseCaseRequestInterface
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
