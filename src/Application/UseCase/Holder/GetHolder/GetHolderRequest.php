<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolder;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class GetHolderRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $id,
    ) {}
}
