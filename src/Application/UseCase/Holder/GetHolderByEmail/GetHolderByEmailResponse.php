<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolderByEmail;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\Holder\InfosHolder;

final readonly class GetHolderByEmailResponse implements UseCaseResponseInterface
{
    public function __construct(
        public InfosHolder $readModel,
    ) {}
}
