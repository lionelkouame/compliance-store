<?php

namespace App\Domain\ValueObject;

final readonly class RegulatoryScopeCode
{
    private const PATTERN = '/^[A-Z][A-Z0-9_]*$/';

    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match(self::PATTERN, $this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'Le code "%s" est invalide : format attendu MAJUSCULES_SNAKE_CASE (ex: KYC_INDIVIDUAL).',
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
