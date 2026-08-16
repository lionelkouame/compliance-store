<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetLegalFrameworkByCode;

use App\Domain\Entity\LegalFramework;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\LegalFrameworkCode;

final readonly class GetLegalFrameworkByCodeUseCase
{
    public function __construct(
        private LegalFrameworkRepositoryInterface $legalFrameworks,
    ) {}

    public function execute(string $code): ?LegalFramework
    {
        if (!LegalFrameworkCode::isValid($code)) {
            return null;
        }

        return $this->legalFrameworks->findByCode(new LegalFrameworkCode($code));
    }
}
