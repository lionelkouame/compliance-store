<?php

declare(strict_types=1);

namespace App\Domain\Exception\Holder;

use App\Domain\Exception\InvalidDataException;
use App\Domain\ValueObject\Holder\HolderStatus;

final class InvalidHolderStatusTransitionException extends InvalidDataException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * La transition demandée n'est pas permise depuis l'état actuel.
     */
    public static function between(HolderStatus $from, HolderStatus $to): self
    {
        return new self(\sprintf(
            'Cannot transition a holder from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }

    /**
     * L'état a été modifié par un autre traitement entre la lecture et
     * l'écriture : la transition, calculée sur un état périmé, est refusée.
     */
    public static function concurrentChange(HolderStatus $to): self
    {
        return new self(\sprintf(
            'The holder status was changed concurrently: it cannot transition to "%s". Reload it and retry.',
            $to->value,
        ));
    }
}
