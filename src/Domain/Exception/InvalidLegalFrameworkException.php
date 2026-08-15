<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidLegalFrameworkException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('The legal framework "%s" is inactive or unknown.', $code));
    }
}
