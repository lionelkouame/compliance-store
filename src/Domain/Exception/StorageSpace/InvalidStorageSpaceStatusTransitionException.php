<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\InvalidDataException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;

final class InvalidStorageSpaceStatusTransitionException extends InvalidDataException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * La transition demandée n'est pas permise depuis l'état actuel.
     */
    public static function between(StorageSpaceStatus $from, StorageSpaceStatus $to): self
    {
        return new self(\sprintf(
            'Cannot transition a storage space from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }

    /**
     * L'état a été modifié par un autre traitement entre la lecture et
     * l'écriture : la transition, calculée sur un état périmé, est refusée.
     */
    public static function concurrentChange(StorageSpaceStatus $to): self
    {
        return new self(\sprintf(
            'The storage space status was changed concurrently: it cannot transition to "%s". Reload it and retry.',
            $to->value,
        ));
    }
}
