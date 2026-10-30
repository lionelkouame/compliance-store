<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolderByEmail;

class GetHolderByEmailRequest
{
    public function __construct(
        public string $email
    ){}
}
