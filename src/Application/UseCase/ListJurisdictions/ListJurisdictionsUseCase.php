<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListJurisdictions;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;

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
        return array_values(array_filter(
            $this->jurisdictions->findAll(),
            static function (Jurisdiction $jurisdiction) use ($query): bool {
                if (null !== $query->region && $jurisdiction->region()->value !== $query->region) {
                    return false;
                }

                if (null !== $query->country && $jurisdiction->country()?->value !== $query->country) {
                    return false;
                }

                if (null !== $query->active && $jurisdiction->isActive() !== $query->active) {
                    return false;
                }

                return true;
            },
        ));
    }
}
