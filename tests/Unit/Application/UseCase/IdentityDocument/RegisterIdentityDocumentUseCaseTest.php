<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\IdentityDocument;

use App\Application\UseCase\IdentityDocument\RegisterIdentityDocument\RegisterIdentityDocumentRequest;
use App\Application\UseCase\IdentityDocument\RegisterIdentityDocument\RegisterIdentityDocumentUseCase;
use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocument\IdentityDocumentAlreadyExistsException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocument\ExpiryDate;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;
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

        $readModel = $this->useCase($identityDocuments)->execute($this->request())->readModel;

        self::assertSame(self::ID, $readModel->id->value());
        self::assertSame(IdentityDocumentType::Passport, $readModel->type);
        self::assertSame('FRA', $readModel->country->value);
        self::assertSame('18AB12345', $readModel->number->value);
        self::assertSame('2035-01-31', (string) $readModel->expiresAt);
    }

    public function testExecuteRejectsADuplicateDocument(): void
    {
        $existing = IdentityDocument::create(
            id: IdentityDocumentId::make(self::ID),
            type: IdentityDocumentType::Passport,
            country: new CountryCode('FRA'),
            number: new IdentityDocumentNumber('18AB12345'),
            expiresAt: ExpiryDate::make('2035-01-31'),
        );

        $identityDocuments = $this->createMock(IdentityDocumentRepositoryInterface::class);
        $identityDocuments->method('findByNumber')->willReturn($existing);
        $identityDocuments->expects(self::never())->method('add');

        $this->expectException(IdentityDocumentAlreadyExistsException::class);

        $this->useCase($identityDocuments)->execute($this->request());
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsInvalidInput(string $type, string $expiresAt): void
    {
        $identityDocuments = $this->createMock(IdentityDocumentRepositoryInterface::class);
        $identityDocuments->expects(self::never())->method('add');

        $this->expectException(InvalidValueException::class);

        $this->useCase($identityDocuments)->execute($this->request(type: $type, expiresAt: $expiresAt));
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
        $idGenerator->method('generate')->willReturn(IdentityDocumentId::make(self::ID));

        return new RegisterIdentityDocumentUseCase(
            identityDocuments: $identityDocuments,
            idGenerator: $idGenerator,
        );
    }

    private function request(string $type = 'Passport', string $expiresAt = '2035-01-31'): RegisterIdentityDocumentRequest
    {
        return new RegisterIdentityDocumentRequest(
            type: $type,
            country: 'FRA',
            number: '18AB12345',
            expiresAt: $expiresAt,
        );
    }
}
