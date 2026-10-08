<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\RegisterIdentityDocument\RegisterIdentityDocumentCommand;
use App\Application\UseCase\RegisterIdentityDocument\RegisterIdentityDocumentUseCase;
use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocumentAlreadyExistsException;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocumentNumber;
use App\Domain\ValueObject\IdentityDocumentType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegisterIdentityDocumentUseCaseTest extends TestCase
{
    private const ID = '0192f0c1-0000-7000-8000-000000000001';

    public function testExecuteRegistersTheDocument(): void
    {
        $identityDocuments = $this->createMock(IdentityDocumentRepositoryInterface::class);
        $identityDocuments->method('findByNumber')->willReturn(null);
        $identityDocuments->expects(self::once())->method('add');

        $identityDocument = $this->useCase($identityDocuments)->execute($this->command());

        self::assertSame(self::ID, $identityDocument->id()->value);
        self::assertSame(IdentityDocumentType::Passport, $identityDocument->type());
        self::assertSame('FRA', $identityDocument->country()->value);
        self::assertSame('18AB12345', $identityDocument->number()->value);
        self::assertSame('2035-01-31', $identityDocument->expiresAt()->format('Y-m-d'));
    }

    public function testExecuteRejectsADuplicateDocument(): void
    {
        $existing = IdentityDocument::create(
            id: IdentityDocumentId::fromString(self::ID),
            type: IdentityDocumentType::Passport,
            country: new CountryCode('FRA'),
            number: new IdentityDocumentNumber('18AB12345'),
            expiresAt: new \DateTimeImmutable('2035-01-31'),
        );

        $identityDocuments = $this->createMock(IdentityDocumentRepositoryInterface::class);
        $identityDocuments->method('findByNumber')->willReturn($existing);
        $identityDocuments->expects(self::never())->method('add');

        $this->expectException(IdentityDocumentAlreadyExistsException::class);

        $this->useCase($identityDocuments)->execute($this->command());
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsInvalidInput(string $type, string $expiresAt): void
    {
        $identityDocuments = $this->createMock(IdentityDocumentRepositoryInterface::class);
        $identityDocuments->expects(self::never())->method('add');

        $this->expectException(\InvalidArgumentException::class);

        $this->useCase($identityDocuments)->execute($this->command(type: $type, expiresAt: $expiresAt));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'unknown type' => ['DrivingLicence', '2035-01-31'];
        yield 'malformed date' => ['Passport', '31/01/2035'];
        yield 'impossible date' => ['Passport', '2035-02-30'];
    }

    private function useCase(IdentityDocumentRepositoryInterface $identityDocuments): RegisterIdentityDocumentUseCase
    {
        $idGenerator = $this->createStub(IdentityDocumentIdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(IdentityDocumentId::fromString(self::ID));

        return new RegisterIdentityDocumentUseCase(
            identityDocuments: $identityDocuments,
            idGenerator: $idGenerator,
        );
    }

    private function command(string $type = 'Passport', string $expiresAt = '2035-01-31'): RegisterIdentityDocumentCommand
    {
        return new RegisterIdentityDocumentCommand(
            type: $type,
            country: 'FRA',
            number: '18AB12345',
            expiresAt: $expiresAt,
        );
    }
}
