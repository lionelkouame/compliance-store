<?php

namespace App\Domain\ValueObject;

final readonly class RegulatoryScopeLabel
{
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The regulatory scope label cannot be empty.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The regulatory scope label cannot exceed %d characters.',
                self::MAX_LENGTH,
            ));
        }
    }
}
