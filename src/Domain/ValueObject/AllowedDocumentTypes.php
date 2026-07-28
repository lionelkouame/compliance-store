<?php

namespace App\Domain\ValueObject;

/**
 * Collection immuable de DocumentType.
 *
 * Aucune méthode `array` n'est exposée publiquement : la collection se consomme
 * via `foreach`, `count()` et `contains()`. Le tableau interne est un détail
 * d'implémentation privé (conversion vers un tableau brut réservée à l'Infrastructure).
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
