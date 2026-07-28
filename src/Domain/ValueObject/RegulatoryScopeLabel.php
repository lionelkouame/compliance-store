<?php

namespace App\Domain\ValueObject;

final readonly class RegulatoryScopeLabel
{
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('Le libellé du périmètre réglementaire ne peut pas être vide.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'Le libellé du périmètre réglementaire ne peut pas dépasser %d caractères.',
                self::MAX_LENGTH,
            ));
        }
    }
}
