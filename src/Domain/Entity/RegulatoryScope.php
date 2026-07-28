<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\AllowedDocumentTypes;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeDescription;
use App\Domain\ValueObject\RegulatoryScopeLabel;

/**
 * Dynamically manageable regulatory scope (No-Code).
 *
 * Types a document flow (e.g. KYC_INDIVIDUAL, GDPR_RETENTION) without requiring
 * redeployment: the list of scopes is never hardcoded (enum).
 */
final class RegulatoryScope
{
    private function __construct(
        private readonly RegulatoryScopeCode $code,
        private RegulatoryScopeLabel $label,
        private RegulatoryScopeDescription $description,
        private AllowedDocumentTypes $allowedDocumentTypes,
        private bool $isActive,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        RegulatoryScopeCode $code,
        RegulatoryScopeLabel $label,
        RegulatoryScopeDescription $description,
        AllowedDocumentTypes $allowedDocumentTypes,
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

    public function code(): RegulatoryScopeCode
    {
        return $this->code;
    }

    public function label(): RegulatoryScopeLabel
    {
        return $this->label;
    }

    public function description(): RegulatoryScopeDescription
    {
        return $this->description;
    }

    public function allowedDocumentTypes(): AllowedDocumentTypes
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
