<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\StoreDocument\StoreDocumentCommand;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotActiveException;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\DocumentIdGeneratorInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\EncryptedPayload;
use App\Domain\ValueObject\EncryptionEnvelope;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;
use App\Domain\ValueObject\WrappedDataKey;
use PHPUnit\Framework\TestCase;

final class StoreDocumentUseCaseTest extends TestCase
{
    private const HOLDER_ID = '770e8400-e29b-41d4-a716-446655440111';

    public function testExecuteEncryptsStoresAndPersistsADocument(): void
    {
        $envelope = new EncryptionEnvelope(
            payload: new EncryptedPayload('cipher-bytes'),
            wrappedDataKey: new WrappedDataKey('wrapped-key-bytes'),
        );

        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::once())->method('encrypt')->with('plaintext content')->willReturn($envelope);

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::once())->method('store')->with(
            self::callback(static fn (DocumentId $id): bool => '660e8400-e29b-41d4-a716-446655440000' === $id->value),
            'cipher-bytes',
        );

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::once())->method('add');

        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());

        $idGenerator = $this->createStub(DocumentIdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(DocumentId::fromString('660e8400-e29b-41d4-a716-446655440000'));

        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $auditTrail->expects(self::once())->method('record')->with(AuditAction::DocumentStored, '660e8400-e29b-41d4-a716-446655440000');

        $useCase = new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            holders: $holders,
            idGenerator: $idGenerator,
            idValidator: $this->acceptingValidator(),
            auditTrail: $auditTrail,
        );

        $document = $useCase->execute(new StoreDocumentCommand(
            content: 'plaintext content',
            holderId: self::HOLDER_ID,
        ));

        self::assertSame('660e8400-e29b-41d4-a716-446655440000', $document->id()->value);
        self::assertSame(self::HOLDER_ID, $document->holderId()->value);
    }

    public function testExecuteRejectsAnUnknownHolderBeforeTouchingStorage(): void
    {
        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::never())->method('encrypt');

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('store');

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::never())->method('add');

        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $useCase = new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            holders: $holders,
            idGenerator: $this->createStub(DocumentIdGeneratorInterface::class),
            idValidator: $this->acceptingValidator(),
            auditTrail: $this->createStub(AuditTrailInterface::class),
        );

        $this->expectException(HolderNotFoundException::class);

        $useCase->execute(new StoreDocumentCommand(
            content: 'plaintext content',
            holderId: self::HOLDER_ID,
        ));
    }

    private function holder(): Holder
    {
        return Holder::register(
            id: HolderId::fromString(self::HOLDER_ID),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada.lovelace@example.com'),
        );
    }

    private function acceptingValidator(): IdValidatorInterface
    {
        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(true);

        return $validator;
    }

    public function testExecuteRejectsAMalformedHolderIdBeforeAnyLookup(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::never())->method('findById');

        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(false);

        $useCase = new StoreDocumentUseCase(
            cipher: $this->createStub(CipherGatewayInterface::class),
            storage: $this->createStub(StorageGatewayInterface::class),
            documents: $this->createStub(DocumentRepositoryInterface::class),
            holders: $holders,
            idGenerator: $this->createStub(DocumentIdGeneratorInterface::class),
            idValidator: $validator,
            auditTrail: $this->createStub(AuditTrailInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);

        $useCase->execute(new StoreDocumentCommand(holderId: 'not-a-uuid', content: 'plaintext content'));
    }

    public function testExecuteRemovesTheCiphertextWhenTheRecordCannotBePersisted(): void
    {
        $envelope = new EncryptionEnvelope(
            payload: new EncryptedPayload('cipher-bytes'),
            wrappedDataKey: new WrappedDataKey('wrapped-key-bytes'),
        );
        $cipher = $this->createStub(CipherGatewayInterface::class);
        $cipher->method('encrypt')->willReturn($envelope);

        $id = DocumentId::fromString('660e8400-e29b-41d4-a716-446655440000');
        $idGenerator = $this->createStub(DocumentIdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn($id);

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::once())->method('store');
        $storage->expects(self::once())->method('delete')->with($id);

        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('add')->willThrowException(new \RuntimeException('database down'));

        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());

        $useCase = new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            holders: $holders,
            idGenerator: $idGenerator,
            idValidator: $this->acceptingValidator(),
            auditTrail: $this->createStub(AuditTrailInterface::class),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('database down');

        $useCase->execute(new StoreDocumentCommand(content: 'plaintext content', holderId: self::HOLDER_ID));
    }

    public function testTheOriginalFailureWinsWhenTheCompensationFailsToo(): void
    {
        $cipher = $this->createStub(CipherGatewayInterface::class);
        $cipher->method('encrypt')->willReturn(new EncryptionEnvelope(
            payload: new EncryptedPayload('cipher-bytes'),
            wrappedDataKey: new WrappedDataKey('wrapped-key-bytes'),
        ));
        $idGenerator = $this->createStub(DocumentIdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(DocumentId::fromString('660e8400-e29b-41d4-a716-446655440000'));

        $storage = $this->createStub(StorageGatewayInterface::class);
        $storage->method('delete')->willThrowException(new \RuntimeException('bucket unreachable'));

        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('add')->willThrowException(new \RuntimeException('database down'));

        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());

        $useCase = new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            holders: $holders,
            idGenerator: $idGenerator,
            idValidator: $this->acceptingValidator(),
            auditTrail: $this->createStub(AuditTrailInterface::class),
        );

        $this->expectExceptionMessage('database down');

        $useCase->execute(new StoreDocumentCommand(content: 'plaintext content', holderId: self::HOLDER_ID));
    }

    public function testExecuteRejectsAHolderWhoAskedForErasureBeforeTouchingStorage(): void
    {
        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::never())->method('encrypt');

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('store');

        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());

        $useCase = new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $this->createStub(DocumentRepositoryInterface::class),
            holders: $holders,
            idGenerator: $this->createStub(DocumentIdGeneratorInterface::class),
            idValidator: $this->acceptingValidator(),
            auditTrail: $this->createStub(AuditTrailInterface::class),
        );

        $this->expectException(HolderNotActiveException::class);

        $useCase->execute(new StoreDocumentCommand(content: 'plaintext content', holderId: self::HOLDER_ID));
    }
}
