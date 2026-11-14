<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateHolderInput
{
    #[Assert\NotBlank(message: 'A holder name is required.')]
    public ?string $holderName = null;

    #[Assert\NotBlank(message: 'A birth date is required.')]
    public ?string $birthDate = null;

    #[Assert\NotBlank(message: 'An email is required.')]
    #[Assert\Email(message: 'The email is not a valid email address.')]
    public ?string $email = null;
}
