<?php

declare(strict_types=1);

namespace App\Domain\Exception\Document;

use App\Domain\Exception\InvalidDataException;

/**
 * A document references a holder that does not exist.
 *
 * Deliberately an InvalidDataException (422) and not a NotFoundException
 * (404): the holder is part of the submitted data, the requested resource
 * itself is not missing (ADR 0013).
 */
final class UnknownDocumentHolderException extends InvalidDataException
{
    public function __construct()
    {
        parent::__construct('The holder of the document does not exist.');
    }
}
