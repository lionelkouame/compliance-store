<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class JurisdictionLabel
{
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The jurisdiction label cannot be empty.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The jurisdiction label cannot exceed %d characters.',
                self::MAX_LENGTH,
            ));
        }
    }
}
