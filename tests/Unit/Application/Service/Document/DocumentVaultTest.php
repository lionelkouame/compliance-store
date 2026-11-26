<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service\Document;

use App\Application\Service\Document\DocumentVault;
use App\Domain\Entity\Document;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\Document\WrappedDataKey;
use App\Domain\ValueObject\Shared\Generic\EncryptedPayload;
use App\Domain\ValueObject\Shared\Generic\EncryptionEnvelope;
use App\Domain\ValueObject\Shared\Identifier\HolderId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DocumentVaultTest extends TestCase
{
    private const DOCUMENT_ID = '660e8400-e29b-41d4-a716-446655440000';
    private const HOLDER_ID = '0192f0c1-0000-7000-8000-0000000000aa';

    public function testStoreEncryptsStoresAndPersistsTheDocument(): void
    {
        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::once())->method('encrypt')->with('plaintext content')->willReturn($this->envelope());

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::once())->method('store')->with(
            self::callback(static fn ($id) => self::DOCUMENT_ID === $id->value()),
            'cipher-bytes',
        );
        $storage->expects(self::never())->method('delete');

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::once())->method('add')->with(
            self::callback(static fn (Document $document): bool => self::HOLDER_ID === $document->holderId()->value()),
        );

        $document = $this->vault($storage, $documents, $cipher)->store(HolderId::make(self::HOLDER_ID), 'plaintext content');

        self::assertSame(self::DOCUMENT_ID, $document->id()->value());
        self::assertSame(self::HOLDER_ID, $document->holderId()->value());
        self::assertSame(hash('sha256', 'plaintext content'), $document->fileHash()->value());
        self::assertSame('wrapped-key-bytes', $document->wrappedDataKey()->value());
    }

    public function testStoreRemovesTheStoredContentWhenTheDatabaseWriteFails(): void
    {
        $failure = new \RuntimeException('Database unavailable.');

        [$storage, $documents] = $this->failingDatabaseWrite($failure);
        $storage->expects(self::once())->method('delete')
            ->with(self::callback(static fn ($id) => self::DOCUMENT_ID === $id->value()));

        try {
            $this->vault($storage, $documents)->store(HolderId::make(self::HOLDER_ID), 'plaintext content');
            self::fail('The database failure must be rethrown.');
        } catch (\RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    public function testTheOriginalFailureIsReportedEvenIfTheCleanupFails(): void
    {
        $failure = new \RuntimeException('Database unavailable.');

        [$storage, $documents] = $this->failingDatabaseWrite($failure);
        $storage->method('delete')->willThrowException(new \RuntimeException('Storage unavailable.'));

        $this->expectExceptionObject($failure);

        $this->vault($storage, $documents)->store(HolderId::make(self::HOLDER_ID), 'plaintext content');
    }

    /**
     * @return array{StorageGatewayInterface&MockObject, DocumentRepositoryInterface}
     */
    private function failingDatabaseWrite(\Throwable $failure): array
    {
        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::once())->method('store');

        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('add')->willThrowException($failure);

        return [$storage, $documents];
    }

    private function vault(
        StorageGatewayInterface $storage,
        DocumentRepositoryInterface $documents,
        ?CipherGatewayInterface $cipher = null,
    ): DocumentVault {
        if (null === $cipher) {
            $cipher = $this->createStub(CipherGatewayInterface::class);
            $cipher->method('encrypt')->willReturn($this->envelope());
        }

        $idGenerator = $this->createStub(GeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::DOCUMENT_ID);

        return new DocumentVault(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            idGenerator: $idGenerator,
        );
    }

    private function envelope(): EncryptionEnvelope
    {
        return new EncryptionEnvelope(
            payload: EncryptedPayload::make('cipher-bytes'),
            wrappedDataKey: WrappedDataKey::make('wrapped-key-bytes'),
        );
    }
}
