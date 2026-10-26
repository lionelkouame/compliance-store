<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\DocumentId;

final class DocumentNotFoundException extends \DomainException
{
    public function __construct(DocumentId $id)
    {
        parent::__construct(\sprintf('No document found with id "%s".', $id->value));
    }
}
