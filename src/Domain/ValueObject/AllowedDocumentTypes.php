<?php

namespace App\Domain\ValueObject;

final readonly class AllowedDocumentTypes
{
    /**
     * @var list<string>
     */
    public array $values;

    /**
     * @param list<string> $values
     */
    public function __construct(array $values)
    {
        foreach ($values as $documentType) {
            if (!\is_string($documentType) || '' === trim($documentType)) {
                throw new \InvalidArgumentException('Un type de document autorisé ne peut pas être vide.');
            }
        }

        $this->values = array_values($values);
    }

    public function contains(string $documentType): bool
    {
        return \in_array($documentType, $this->values, true);
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
