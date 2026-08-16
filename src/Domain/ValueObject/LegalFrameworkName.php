<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class LegalFrameworkName
{
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The legal framework name cannot be empty.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The legal framework name cannot exceed %d characters.',
                self::MAX_LENGTH,
            ));
        }
    }
}
