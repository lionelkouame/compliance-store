<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class RegulatoryScopeCode
{
    private const PATTERN = '/^[A-Z][A-Z0-9_]*$/';

    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match(self::PATTERN, $this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The code "%s" is invalid: expected format is UPPERCASE_SNAKE_CASE (e.g. KYC_INDIVIDUAL).',
                $this->value,
            ));
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Required by Doctrine's UnitOfWork, which builds identity-map hashes via
     * implode() on identifier values without going through the custom DBAL type.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
