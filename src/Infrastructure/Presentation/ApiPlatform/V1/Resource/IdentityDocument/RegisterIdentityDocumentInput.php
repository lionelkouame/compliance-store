<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\IdentityDocument;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Only presence is checked here: format rules (country code, number, date)
 * are enforced by the Domain (ADR 0005, double lock).
 */
final class RegisterIdentityDocumentInput
{
    #[Assert\NotBlank(message: 'An identity document type is required.')]
    public ?string $type = null;

    #[Assert\NotBlank(message: 'An issuing country is required.')]
    public ?string $country = null;

    #[Assert\NotBlank(message: 'An identity document number is required.')]
    public ?string $number = null;

    #[Assert\NotBlank(message: 'An expiry date is required.')]
    public ?string $expiresAt = null;
}
