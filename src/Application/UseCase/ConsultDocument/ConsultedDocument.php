<?php

declare(strict_types=1);

namespace App\Application\UseCase\ConsultDocument;

use App\Domain\Entity\Document;

/**
 * Result of a consultation: the document and its decrypted, integrity-checked content.
 */
final readonly class ConsultedDocument
{
    public function __construct(
        public Document $document,
        public string $content,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['document' => $this->document->id()->value, 'content' => '***'];
    }
}
