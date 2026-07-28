<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateRegulatoryScope;

final readonly class CreateRegulatoryScopeCommand
{
    /**
     * @param list<string> $allowedDocumentTypes
     */
    public function __construct(
        public string $code,
        public string $label,
        public string $description,
        public array $allowedDocumentTypes,
        public bool $isActive = true,
    ) {}
}
