<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkId;
use App\Domain\ValueObject\LegalFrameworkName;
use App\Domain\ValueObject\LegalFrameworkOfficialReference;
use App\Domain\ValueObject\LegalFrameworkRegulatoryAuthority;

/**
 * Dynamically manageable legal/regulatory framework (No-Code).
 *
 * Types the legal basis a storage policy can be justified
 * by (e.g. FRAMEWORK-GDPR, FRAMEWORK-EIDAS2, FRAMEWORK-SEC-17A4) without
 * requiring redeployment: the list of frameworks is never hardcoded (enum).
 */
final class LegalFramework
{
    private function __construct(
        private readonly LegalFrameworkId $id,
        private readonly LegalFrameworkCode $code,
        private LegalFrameworkName $name,
        private LegalFrameworkOfficialReference $officialReference,
        private LegalFrameworkRegulatoryAuthority $regulatoryAuthority,
        private JurisdictionCode $jurisdictionCode,
        private bool $active,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        LegalFrameworkId $id,
        LegalFrameworkCode $code,
        LegalFrameworkName $name,
        LegalFrameworkOfficialReference $officialReference,
        LegalFrameworkRegulatoryAuthority $regulatoryAuthority,
        JurisdictionCode $jurisdictionCode,
        bool $active = true,
    ): self {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return new self(
            id: $id,
            code: $code,
            name: $name,
            officialReference: $officialReference,
            regulatoryAuthority: $regulatoryAuthority,
            jurisdictionCode: $jurisdictionCode,
            active: $active,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function id(): LegalFrameworkId
    {
        return $this->id;
    }

    public function code(): LegalFrameworkCode
    {
        return $this->code;
    }

    public function name(): LegalFrameworkName
    {
        return $this->name;
    }

    public function officialReference(): LegalFrameworkOfficialReference
    {
        return $this->officialReference;
    }

    public function regulatoryAuthority(): LegalFrameworkRegulatoryAuthority
    {
        return $this->regulatoryAuthority;
    }

    public function jurisdictionCode(): JurisdictionCode
    {
        return $this->jurisdictionCode;
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
