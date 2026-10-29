<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateHolder;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class CreateHolderRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $holderName,
        public string $birthDate,
        public string $email,
    ){}

}
