<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

final class CreateHolderOutput
{
    function __construct(
        public string $identifier
    ){}
}
