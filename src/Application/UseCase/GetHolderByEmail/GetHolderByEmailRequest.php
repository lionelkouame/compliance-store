<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetHolderByEmail;

class GetHolderByEmailRequest
{
    public function __construct(
        public string $email
    ){}
}
