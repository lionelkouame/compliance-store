<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolderByEmail;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class GetHolderByEmailRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $email,
    ) {}
}
