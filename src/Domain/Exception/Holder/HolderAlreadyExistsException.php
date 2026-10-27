<?php

namespace App\Domain\Exception\Holder;

use App\Domain\ValueObject\Holder\HolderId;

class HolderAlreadyExistsException extends \DomainException
{
    public function __construct(HolderId $holderId)
    {
        parent::__construct(\sprintf('A holder with string "%s" already exists.', $holderId));
    }

}
