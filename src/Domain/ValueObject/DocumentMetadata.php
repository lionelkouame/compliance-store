<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class DocumentMetadata
{
    private const MIN_RETENTION_YEARS = 1;
    private const MAX_RETENTION_YEARS = 50;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public string $country,
        public int $retentionYears,
        public array $attributes = [],
    ) {
        if (1 !== preg_match('/^[A-Z]{3}$/', $this->country)) {
            throw new \InvalidArgumentException(\sprintf(
                'The country "%s" must be a 3-letter uppercase ISO code (e.g. FRA).',
                $this->country,
            ));
        }

        if ($this->retentionYears < self::MIN_RETENTION_YEARS || $this->retentionYears > self::MAX_RETENTION_YEARS) {
            throw new \InvalidArgumentException(\sprintf(
                'The retention period must be between %d and %d years.',
                self::MIN_RETENTION_YEARS,
                self::MAX_RETENTION_YEARS,
            ));
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->attributes);
    }
}
