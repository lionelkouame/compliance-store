<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderStatus;

/**
 * A document can only be attached to an active holder: once erasure has been
 * requested, no new personal data may be collected for that data subject.
 */
final class HolderNotActiveException extends \DomainException
{
    public function __construct(HolderId $id, HolderStatus $status)
    {
        parent::__construct(\sprintf('The holder "%s" is not active (status "%s"): no document can be stored for it.', $id->value, $status->value));
    }
}
