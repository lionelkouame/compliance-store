<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Immutable collection of DocumentType.
 *
 * No raw array is exposed publicly: the collection is consumed via
 * `foreach`, `count()`, and `contains()`. The internal array is a private
 * implementation detail (conversion to a plain array is reserved for Infrastructure).
 *
 * @implements \IteratorAggregate<int, DocumentType>
 */
final readonly class AllowedDocumentTypes implements \IteratorAggregate, \Countable
{
    /**
     * @var list<DocumentType>
     */
    private array $documentTypes;

    public function __construct(DocumentType ...$documentTypes)
    {
        $this->documentTypes = array_values($documentTypes);
    }

    public static function fromStrings(string ...$values): self
    {
        return new self(...array_map(
            static fn (string $value): DocumentType => new DocumentType($value),
            $values,
        ));
    }

    public function contains(DocumentType $documentType): bool
    {
        foreach ($this->documentTypes as $existing) {
            if ($existing->equals($documentType)) {
                return true;
            }
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return [] === $this->documentTypes;
    }

    public function count(): int
    {
        return \count($this->documentTypes);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->documentTypes;
    }
}
