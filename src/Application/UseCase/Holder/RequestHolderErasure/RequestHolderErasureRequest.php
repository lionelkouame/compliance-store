<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\RequestHolderErasure;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class RequestHolderErasureRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $id,
    ) {}
}
