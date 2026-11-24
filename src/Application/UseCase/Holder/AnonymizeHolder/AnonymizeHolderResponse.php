<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\AnonymizeHolder;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\Holder\InfosHolder;

final readonly class AnonymizeHolderResponse implements UseCaseResponseInterface
{
    public function __construct(
        public InfosHolder $readModel,
    ) {}
}
