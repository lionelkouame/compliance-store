<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListJurisdictions;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCountry;
use App\Domain\ValueObject\JurisdictionRegion;

final readonly class ListJurisdictionsUseCase
{
    public function __construct(
        private JurisdictionRepositoryInterface $jurisdictions,
    ) {}

    /**
     * @return list<Jurisdiction>
     */
    public function execute(ListJurisdictionsQuery $query = new ListJurisdictionsQuery()): array
    {
        // Conditional VO instantiation belongs here, not in the Domain Port
        // (ADR 0006). A malformed filter value can never match a persisted
        // Jurisdiction (its VOs are validated at creation time), so it short-
        // circuits to an empty result instead of throwing or being ignored.
        if (null !== $query->region && !JurisdictionRegion::isValid($query->region)) {
            return [];
        }

        if (null !== $query->country && !JurisdictionCountry::isValid($query->country)) {
            return [];
        }

        return $this->jurisdictions->findAllMatching(
            region: null !== $query->region ? new JurisdictionRegion($query->region) : null,
            country: null !== $query->country ? new JurisdictionCountry($query->country) : null,
            active: $query->active,
        );
    }
}
