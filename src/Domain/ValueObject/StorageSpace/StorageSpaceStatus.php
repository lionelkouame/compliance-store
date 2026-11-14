<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\StorageSpace;

use App\Domain\Exception\InvalidValueException;

/**
 * Lifecycle of a StorageSpace:
 *
 *     Active <-> Inactive
 *        \        /
 *         Archived   (terminal: an archive is never reopened)
 */
enum StorageSpaceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    /**
     * @throws InvalidValueException when $value is not a known status
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidValueException(\sprintf(
            'The storage space status "%s" must be one of: %s.',
            $value,
            implode(', ', array_column(self::cases(), 'value')),
        ));
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => self::Inactive === $target || self::Archived === $target,
            self::Inactive => self::Active === $target || self::Archived === $target,
            self::Archived => false,
        };
    }
}
