<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListLegalFrameworks;

use App\Domain\Entity\LegalFramework;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;

final readonly class ListLegalFrameworksUseCase
{
    public function __construct(
        private LegalFrameworkRepositoryInterface $legalFrameworks,
    ) {}

    /**
     * @return list<LegalFramework>
     */
    public function execute(ListLegalFrameworksQuery $query = new ListLegalFrameworksQuery()): array
    {
        return array_values(array_filter(
            $this->legalFrameworks->findAll(),
            static function (LegalFramework $legalFramework) use ($query): bool {
                if (null !== $query->jurisdictionCode && $legalFramework->jurisdictionCode()->value !== $query->jurisdictionCode) {
                    return false;
                }

                if (null !== $query->active && $legalFramework->isActive() !== $query->active) {
                    return false;
                }

                return true;
            },
        ));
    }
}
