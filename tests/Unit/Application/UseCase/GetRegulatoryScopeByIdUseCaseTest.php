<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\GetRegulatoryScopeById\GetRegulatoryScopeByIdUseCase;
use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AllowedDocumentTypes;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeDescription;
use App\Domain\ValueObject\RegulatoryScopeId;
use App\Domain\ValueObject\RegulatoryScopeLabel;
use PHPUnit\Framework\TestCase;

final class GetRegulatoryScopeByIdUseCaseTest extends TestCase
{
    public function testExecuteReturnsNullForInvalidUuidWithoutQueryingRepository(): void
    {
        $repository = $this->createMock(RegulatoryScopeRepositoryInterface::class);
        $validator = $this->createMock(IdValidatorInterface::class);

        $validator->expects(self::once())
            ->method('isValid')
            ->with('not-a-valid-uuid')
            ->willReturn(false);

        $repository->expects(self::never())->method('findById');

        $useCase = new GetRegulatoryScopeByIdUseCase($repository, $validator);
        $result = $useCase->execute('not-a-valid-uuid');

        self::assertNull($result);
    }

    public function testExecuteQueriesRepositoryForValidUuid(): void
    {
        $uuidStr = '550e8400-e29b-41d4-a716-446655440000';
        $repository = $this->createMock(RegulatoryScopeRepositoryInterface::class);
        $validator = $this->createMock(IdValidatorInterface::class);

        $validator->expects(self::atLeastOnce())
            ->method('isValid')
            ->with($uuidStr)
            ->willReturn(true);

        $expectedScope = RegulatoryScope::create(
            RegulatoryScopeId::fromString($uuidStr, $validator),
            new RegulatoryScopeCode('KYC_INDIVIDUAL'),
            new RegulatoryScopeLabel('KYC Individual'),
            new RegulatoryScopeDescription('Scope description'),
            AllowedDocumentTypes::fromStrings('ID_CARD')
        );

        $repository->expects(self::once())
            ->method('findById')
            ->with(self::callback(fn (RegulatoryScopeId $id) => $uuidStr === $id->value))
            ->willReturn($expectedScope);

        $useCase = new GetRegulatoryScopeByIdUseCase($repository, $validator);
        $result = $useCase->execute($uuidStr);

        self::assertSame($expectedScope, $result);
    }
}
