<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\AnonymizeHolder;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class AnonymizeHolderRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $id,
    ) {}
}
