<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class NonCompliantDocumentException extends \DomainException
{
    public static function forDocumentType(string $documentType): self
    {
        return new self(\sprintf(
            'The document type "%s" is not covered by any active regulatory scope.',
            $documentType,
        ));
    }
}
