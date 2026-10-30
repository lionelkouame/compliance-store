<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetHolderByEmail;

use App\Domain\Entity\Holder;

class GetHolderByEmailResponse
{
    public function __construct(
        public Holder $holder
    )
    {}
}
