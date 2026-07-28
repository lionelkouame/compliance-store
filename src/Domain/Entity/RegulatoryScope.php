<?php

namespace App\Domain\Entity;

/**
 * Périmètre réglementaire administrable dynamiquement (No-Code).
 *
 * Type un flux de documents (ex: KYC_INDIVIDUAL, GDPR_RETENTION) sans nécessiter
 * de redéploiement : la liste des périmètres n'est jamais figée en code (enum).
 */
final class RegulatoryScope
{
    /**
     * @param list<string> $allowedDocumentTypes
     */
    private function __construct(
        private readonly string $code,
        private string $label,
        private string $description,
        private array $allowedDocumentTypes,
        private bool $isActive,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param list<string> $allowedDocumentTypes
     */
    public static function create(
        string $code,
        string $label,
        string $description,
        array $allowedDocumentTypes,
        bool $isActive = true,
    ): self {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return new self(
            code: $code,
            label: $label,
            description: $description,
            allowedDocumentTypes: $allowedDocumentTypes,
            isActive: $isActive,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function code(): string
    {
        return $this->code;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function allowedDocumentTypes(): array
    {
        return $this->allowedDocumentTypes;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
