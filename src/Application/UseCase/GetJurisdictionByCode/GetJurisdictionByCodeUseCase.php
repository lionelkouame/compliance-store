<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetJurisdictionByCode;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;

final readonly class GetJurisdictionByCodeUseCase
{
    public function __construct(
        private JurisdictionRepositoryInterface $jurisdictions,
    ) {}

    public function execute(string $code): ?Jurisdiction
    {
        if (!JurisdictionCode::isValid($code)) {
            return null;
        }

        return $this->jurisdictions->findByCode(new JurisdictionCode($code));
    }
}
