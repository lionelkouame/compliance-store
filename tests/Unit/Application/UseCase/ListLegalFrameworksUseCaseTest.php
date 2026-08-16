<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\ListLegalFrameworks\ListLegalFrameworksQuery;
use App\Application\UseCase\ListLegalFrameworks\ListLegalFrameworksUseCase;
use App\Domain\Entity\LegalFramework;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkId;
use App\Domain\ValueObject\LegalFrameworkName;
use App\Domain\ValueObject\LegalFrameworkOfficialReference;
use App\Domain\ValueObject\LegalFrameworkRegulatoryAuthority;
use PHPUnit\Framework\TestCase;

final class ListLegalFrameworksUseCaseTest extends TestCase
{
    private function legalFramework(string $code, string $jurisdictionCode, bool $active): LegalFramework
    {
        return LegalFramework::create(
            id: LegalFrameworkId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            code: new LegalFrameworkCode($code),
            name: new LegalFrameworkName($code),
            officialReference: new LegalFrameworkOfficialReference('Official reference'),
            regulatoryAuthority: new LegalFrameworkRegulatoryAuthority('Authority'),
            jurisdictionCode: new JurisdictionCode($jurisdictionCode),
            active: $active,
        );
    }

    public function testExecuteReturnsAllLegalFrameworksWithoutFilters(): void
    {
        $legalFrameworks = [
            $this->legalFramework('FRAMEWORK-GDPR', 'JUR-EU-GLOBAL', true),
            $this->legalFramework('FRAMEWORK-EIDAS2', 'JUR-EU-GLOBAL', true),
            $this->legalFramework('FRAMEWORK-SEC-17A4', 'JUR-US-CA', false),
        ];

        $repository = $this->createStub(LegalFrameworkRepositoryInterface::class);
        $repository->method('findAll')->willReturn($legalFrameworks);

        $useCase = new ListLegalFrameworksUseCase($repository);
        $result = $useCase->execute();

        self::assertCount(3, $result);
    }

    public function testExecuteFiltersByJurisdictionCodeAndActive(): void
    {
        $euActive = $this->legalFramework('FRAMEWORK-GDPR', 'JUR-EU-GLOBAL', true);
        $euInactive = $this->legalFramework('FRAMEWORK-EIDAS2', 'JUR-EU-GLOBAL', false);
        $usActive = $this->legalFramework('FRAMEWORK-SEC-17A4', 'JUR-US-CA', true);

        $repository = $this->createStub(LegalFrameworkRepositoryInterface::class);
        $repository->method('findAll')->willReturn([$euActive, $euInactive, $usActive]);

        $useCase = new ListLegalFrameworksUseCase($repository);
        $result = $useCase->execute(new ListLegalFrameworksQuery(jurisdictionCode: 'JUR-EU-GLOBAL', active: true));

        self::assertCount(1, $result);
        self::assertSame($euActive, $result[0]);
    }
}
