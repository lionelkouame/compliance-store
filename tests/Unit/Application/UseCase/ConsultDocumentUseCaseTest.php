<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\ConsultDocument\ConsultDocumentUseCase;
use App\Domain\Entity\Document;
use App\Domain\Exception\DocumentIntegrityException;
use App\Domain\Exception\DocumentNotFoundException;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\FileHash;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\WrappedDataKey;
use PHPUnit\Framework\TestCase;

final class ConsultDocumentUseCaseTest extends TestCase
{
    private const DOCUMENT_ID = '660e8400-e29b-41d4-a716-446655440000';

    public function testExecuteReturnsTheDecryptedContentAndAuditsTheRead(): void
    {
        $document = $this->document('the original content');

        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('findById')->willReturn($document);

        $storage = $this->createStub(StorageGatewayInterface::class);
        $storage->method('read')->willReturn('cipher-bytes');

        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::once())->method('decrypt')->willReturn('the original content');

        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $auditTrail->expects(self::once())->method('record')->with(AuditAction::DocumentConsulted, self::DOCUMENT_ID);

        $consulted = $this->useCase($documents, $storage, $cipher, $auditTrail)->execute(self::DOCUMENT_ID);

        self::assertSame('the original content', $consulted->content);
        self::assertSame($document, $consulted->document);
        self::assertStringNotContainsString('original', print_r($consulted, true));
    }

    public function testExecuteRefusesContentThatDoesNotMatchTheRecordedHash(): void
    {
        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('findById')->willReturn($this->document('the original content'));

        $storage = $this->createStub(StorageGatewayInterface::class);
        $storage->method('read')->willReturn('cipher-bytes');

        $cipher = $this->createStub(CipherGatewayInterface::class);
        $cipher->method('decrypt')->willReturn('tampered content');

        $auditTrail = $this->createMock(AuditTrailInterface::class);
        $auditTrail->expects(self::never())->method('record');

        $this->expectException(DocumentIntegrityException::class);

        $this->useCase($documents, $storage, $cipher, $auditTrail)->execute(self::DOCUMENT_ID);
    }

    public function testExecuteFailsForAnUnknownDocumentWithoutTouchingStorage(): void
    {
        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('findById')->willReturn(null);

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('read');

        $this->expectException(DocumentNotFoundException::class);

        $this->useCase($documents, $storage, $this->createStub(CipherGatewayInterface::class), $this->createStub(AuditTrailInterface::class))
            ->execute(self::DOCUMENT_ID);
    }

    private function useCase(
        DocumentRepositoryInterface $documents,
        StorageGatewayInterface $storage,
        CipherGatewayInterface $cipher,
        AuditTrailInterface $auditTrail,
    ): ConsultDocumentUseCase {
        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(true);

        return new ConsultDocumentUseCase($documents, $storage, $cipher, $auditTrail, $validator);
    }

    private function document(string $plaintext): Document
    {
        return Document::create(
            id: DocumentId::fromString(self::DOCUMENT_ID),
            holderId: HolderId::fromString('770e8400-e29b-41d4-a716-446655440111'),
            fileHash: FileHash::fromPlaintext($plaintext),
            wrappedDataKey: new WrappedDataKey('wrapped-key-bytes'),
        );
    }
}
