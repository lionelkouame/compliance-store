<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\GetRegulatoryScopeByCode\GetRegulatoryScopeByCodeUseCase;
use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeId;
use PHPUnit\Framework\TestCase;

final class GetRegulatoryScopeByCodeUseCaseTest extends TestCase
{
    public function testExecuteReturnsNullForInvalidCodeFormatWithoutQueryingRepository(): void
    {
        $repository = $this->createMock(RegulatoryScopeRepositoryInterface::class);
        $repository->expects(self::never())->method('findByCode');

        $useCase = new GetRegulatoryScopeByCodeUseCase($repository);
        $result = $useCase->execute('invalid-code');

        self::assertNull($result);
    }

    public function testExecuteQueriesRepositoryForValidCode(): void
    {
        $repository = $this->createMock(RegulatoryScopeRepositoryInterface::class);
        $expectedScope = RegulatoryScope::create(
            RegulatoryScopeId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            new RegulatoryScopeCode('KYC_INDIVIDUAL'),
            new \App\Domain\ValueObject\RegulatoryScopeLabel('KYC Individual'),
            new \App\Domain\ValueObject\RegulatoryScopeDescription('Scope description'),
            \App\Domain\ValueObject\AllowedDocumentTypes::fromStrings('ID_CARD')
        );

        $repository->expects(self::once())
            ->method('findByCode')
            ->with(self::callback(fn (RegulatoryScopeCode $code) => 'KYC_INDIVIDUAL' === $code->value))
            ->willReturn($expectedScope);

        $useCase = new GetRegulatoryScopeByCodeUseCase($repository);
        $result = $useCase->execute('KYC_INDIVIDUAL');

        self::assertSame($expectedScope, $result);
    }
}
