<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateNaturalPersonInput
{
    public function __construct(
        public ?string $gender = null,
        #[Assert\NotBlank(message: 'A first name is required.')]
        public ?string $firstName = null,
        #[Assert\NotBlank(message: 'A last name is required.')]
        public ?string $lastName = null,
        #[Assert\NotBlank(message: 'A birth date is required.')]
        public ?string $birthday = null,
    ) {}
}
