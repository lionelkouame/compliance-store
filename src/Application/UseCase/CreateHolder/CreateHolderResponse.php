<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateHolder;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\Entity\Holder;
use App\Domain\ValueObject\Holder\HolderId;

final readonly class CreateHolderResponse implements UseCaseResponseInterface
{
    public function __construct(
        public HolderId $holderId,
    ){}

}
