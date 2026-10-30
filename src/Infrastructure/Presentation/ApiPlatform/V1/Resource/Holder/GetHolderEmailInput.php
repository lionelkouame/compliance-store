<?php

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetHolderEmailInput
{
    public function __construct(
        #[Assert\Email(message: 'Email is not valid.')]
        public string $email
    ){}
}
