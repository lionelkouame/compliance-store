<?php

declare(strict_types=1);

namespace App\Domain\Exception\Holder;

use App\Domain\Exception\InvalidDataException;
use App\Domain\ValueObject\Holder\HolderStatus;

final class InvalidHolderStatusTransitionException extends InvalidDataException
{
    public function __construct(HolderStatus $from, HolderStatus $to)
    {
        parent::__construct(\sprintf(
            'Cannot transition a holder from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
