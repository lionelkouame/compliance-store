<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\HolderId;

final class HolderNotFoundException extends \DomainException
{
    public function __construct(HolderId $id)
    {
        parent::__construct(\sprintf('No holder found with id "%s".', $id->value));
    }
}
