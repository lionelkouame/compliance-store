<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\Email;

final class HolderAlreadyExistsException extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * The email identifies a holder: two holders cannot share it.
     */
    public static function withEmail(Email $email): self
    {
        return new self(\sprintf('A holder with email "%s" already exists.', $email->value));
    }
}
