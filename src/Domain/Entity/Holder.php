<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;

/**
 * The natural person a sensitive document belongs to (data subject under
 * GDPR). Aggregate root: documents reference it through its HolderId.
 *
 * Properties are readonly: a lifecycle change (erasure request,
 * anonymization) produces a new instance rather than mutating the current
 * one, and the repository is responsible for persisting it. This mirrors the
 * approach taken by {@see StorageSpace}.
 */
final readonly class Holder
{
    /**
     * @param ?\DateTimeImmutable $consentCollectedAt when consent was collected; null when processing relies on another ground
     * @param ?\DateTimeImmutable $erasureRequestedAt when the data subject asked for erasure (GDPR art. 17); null otherwise
     * @param ?\DateTimeImmutable $anonymizedAt       when erasure was carried out; null otherwise
     */
    private function __construct(
        private HolderId $id,
        private HolderName $name,
        private BirthDate $birthDate,
        private HolderStatus $status,
        private Email $email,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $consentCollectedAt,
        private ?\DateTimeImmutable $erasureRequestedAt,
        private ?\DateTimeImmutable $anonymizedAt,
    ) {}

    public static function create(
        HolderId $id,
        HolderName $name,
        BirthDate $birthDate,
        Email $email,
        ?\DateTimeImmutable $consentCollectedAt = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            birthDate: $birthDate,
            status: HolderStatus::Active,
            email: $email,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            consentCollectedAt: $consentCollectedAt,
            erasureRequestedAt: null,
            anonymizedAt: null,
        );
    }

    /**
     * Records a GDPR erasure request (art. 17). Only an active holder can
     * move to the ErasureRequested state.
     *
     * @throws InvalidHolderStatusTransitionException
     */
    public function requestErasure(): self
    {
        if (HolderStatus::Active !== $this->status) {
            throw new InvalidHolderStatusTransitionException($this->status, HolderStatus::ErasureRequested);
        }

        return new self(
            id: $this->id,
            name: $this->name,
            birthDate: $this->birthDate,
            status: HolderStatus::ErasureRequested,
            email: $this->email,
            createdAt: $this->createdAt,
            consentCollectedAt: $this->consentCollectedAt,
            erasureRequestedAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            anonymizedAt: $this->anonymizedAt,
        );
    }

    /**
     * Carries out the erasure by anonymizing the holder. Only a holder with a
     * pending erasure request can be anonymized.
     *
     * @throws InvalidHolderStatusTransitionException
     */
    public function anonymize(HolderName $anonymizedName): self
    {
        if (HolderStatus::ErasureRequested !== $this->status) {
            throw new InvalidHolderStatusTransitionException($this->status, HolderStatus::Anonymized);
        }

        return new self(
            id: $this->id,
            name: $anonymizedName,
            birthDate: $this->birthDate,
            status: HolderStatus::Anonymized,
            email: $this->email,
            createdAt: $this->createdAt,
            consentCollectedAt: $this->consentCollectedAt,
            erasureRequestedAt: $this->erasureRequestedAt,
            anonymizedAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function id(): HolderId
    {
        return $this->id;
    }

    public function name(): HolderName
    {
        return $this->name;
    }

    public function birthDate(): BirthDate
    {
        return $this->birthDate;
    }

    public function status(): HolderStatus
    {
        return $this->status;
    }
    public function email(): Email
    {
        return $this->email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function consentCollectedAt(): ?\DateTimeImmutable
    {
        return $this->consentCollectedAt;
    }

    public function erasureRequestedAt(): ?\DateTimeImmutable
    {
        return $this->erasureRequestedAt;
    }

    public function anonymizedAt(): ?\DateTimeImmutable
    {
        return $this->anonymizedAt;
    }
}
