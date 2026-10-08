<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class IdentityDocumentExpiredException extends \DomainException
{
    public function __construct(\DateTimeImmutable $expiresAt)
    {
        parent::__construct(\sprintf('The identity document expired on %s.', $expiresAt->format('Y-m-d')));
    }
}
