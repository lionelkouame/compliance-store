<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\StoreDocument\StoreDocumentCommand;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\DocumentIdGeneratorInterface;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\EncryptedPayload;
use App\Domain\ValueObject\EncryptionEnvelope;
use App\Domain\ValueObject\WrappedDataKey;
use PHPUnit\Framework\TestCase;

final class StoreDocumentUseCaseTest extends TestCase
{
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
            self::callback(static fn ($key) => 'documents/660e8400-e29b-41d4-a716-446655440000' === $key->value),
            'cipher-bytes',
        );

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::once())->method('add');

        $idGenerator = $this->createStub(DocumentIdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(DocumentId::fromString('660e8400-e29b-41d4-a716-446655440000'));

        $useCase = new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            idGenerator: $idGenerator,
        );

        $document = $useCase->execute(new StoreDocumentCommand(
            documentType: 'PASSPORT',
            ownerId: 'usr_123',
            content: 'plaintext content',
        ));

        self::assertSame('PASSPORT', $document->documentType()->value);
        self::assertSame('documents/660e8400-e29b-41d4-a716-446655440000', $document->storageKey()->value);
    }
}
