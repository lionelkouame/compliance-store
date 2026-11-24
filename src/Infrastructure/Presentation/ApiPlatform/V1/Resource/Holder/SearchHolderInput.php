<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Critère de recherche d'un titulaire. L'email voyage dans le corps de la
 * requête, jamais dans l'URL (constat M6).
 */
final class SearchHolderInput
{
    #[Assert\NotBlank(message: 'An email is required.')]
    #[Assert\Email(message: 'The email is not a valid email address.')]
    public ?string $email = null;
}
