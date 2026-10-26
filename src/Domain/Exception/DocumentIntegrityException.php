<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\DocumentId;

/**
 * The decrypted content does not match the hash recorded when the document
 * was stored: the ciphertext or its key was altered, or a key/ciphertext pair
 * was swapped between two documents. The content must not be released.
 */
final class DocumentIntegrityException extends \DomainException
{
    public function __construct(DocumentId $id)
    {
        parent::__construct(\sprintf('The content of document "%s" does not match its recorded hash.', $id->value));
    }
}
