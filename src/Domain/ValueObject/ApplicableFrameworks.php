<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Immutable collection of LegalFrameworkCode.
 *
 * No raw array is exposed publicly: the collection is consumed via
 * `foreach`, `count()`, and `contains()`. The internal array is a private
 * implementation detail (conversion to a plain array is reserved for Infrastructure).
 *
 * @implements \IteratorAggregate<int, LegalFrameworkCode>
 */
final readonly class ApplicableFrameworks implements \IteratorAggregate, \Countable
{
    /**
     * @var list<LegalFrameworkCode>
     */
    private array $frameworks;

    public function __construct(LegalFrameworkCode ...$frameworks)
    {
        $this->frameworks = array_values($frameworks);
    }

    public static function fromStrings(string ...$values): self
    {
        return new self(...array_map(
            static fn (string $value): LegalFrameworkCode => new LegalFrameworkCode($value),
            $values,
        ));
    }

    public function contains(LegalFrameworkCode $framework): bool
    {
        foreach ($this->frameworks as $existing) {
            if ($existing->equals($framework)) {
                return true;
            }
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return [] === $this->frameworks;
    }

    public function count(): int
    {
        return \count($this->frameworks);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->frameworks;
    }
}
