<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Regulator/authority name (e.g. "CNIL / EDPB", "SEC", "FINRA", "BaFin").
 */
final readonly class LegalFrameworkRegulatoryAuthority
{
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The legal framework regulatory authority cannot be empty.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The legal framework regulatory authority cannot exceed %d characters.',
                self::MAX_LENGTH,
            ));
        }
    }
}
