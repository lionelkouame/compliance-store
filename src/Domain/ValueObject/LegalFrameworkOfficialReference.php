<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Official publication reference (e.g. "OJEU L 119, 4.5.2016", "Commercial Code Art. L123-22").
 */
final readonly class LegalFrameworkOfficialReference
{
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The legal framework official reference cannot be empty.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The legal framework official reference cannot exceed %d characters.',
                self::MAX_LENGTH,
            ));
        }
    }
}
