<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ApplicableFrameworks;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionCountry;
use App\Domain\ValueObject\JurisdictionId;
use App\Domain\ValueObject\JurisdictionLabel;
use App\Domain\ValueObject\JurisdictionRegion;
use App\Domain\ValueObject\JurisdictionSubRegion;

/**
 * Dynamically manageable territorial jurisdiction (No-Code).
 *
 * Types the geographic scope a RegulatoryScope or storage policy can be
 * attached to (e.g. JUR-EU-FRA, JUR-US-CA, JUR-GLOBAL) without requiring
 * redeployment: the list of jurisdictions is never hardcoded (enum).
 */
final class Jurisdiction
{
    private function __construct(
        private readonly JurisdictionId $id,
        private readonly JurisdictionCode $code,
        private JurisdictionLabel $label,
        private JurisdictionRegion $region,
        private ?JurisdictionCountry $country,
        private ?JurisdictionSubRegion $subRegion,
        private ApplicableFrameworks $applicableFrameworks,
        private bool $active,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        JurisdictionId $id,
        JurisdictionCode $code,
        JurisdictionLabel $label,
        JurisdictionRegion $region,
        ?JurisdictionCountry $country,
        ?JurisdictionSubRegion $subRegion,
        ApplicableFrameworks $applicableFrameworks,
        bool $active = true,
    ): self {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return new self(
            id: $id,
            code: $code,
            label: $label,
            region: $region,
            country: $country,
            subRegion: $subRegion,
            applicableFrameworks: $applicableFrameworks,
            active: $active,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function id(): JurisdictionId
    {
        return $this->id;
    }

    public function code(): JurisdictionCode
    {
        return $this->code;
    }

    public function label(): JurisdictionLabel
    {
        return $this->label;
    }

    public function region(): JurisdictionRegion
    {
        return $this->region;
    }

    public function country(): ?JurisdictionCountry
    {
        return $this->country;
    }

    public function subRegion(): ?JurisdictionSubRegion
    {
        return $this->subRegion;
    }

    public function applicableFrameworks(): ApplicableFrameworks
    {
        return $this->applicableFrameworks;
    }

    public function isActive(): bool
    {
        return $this->active;
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
        $this->active = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
