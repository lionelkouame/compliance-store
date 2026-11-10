<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

use App\Application\UseCase\UseCaseInterface;
use App\Domain\Entity\Document;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Holder\HolderId;
use Webmozart\Assert\Assert;

final readonly class StoreDocumentUseCase
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private GeneratorInterface $idGenerator,
    ) {}

    public function execute(UseCaseInterface $storeDocumentRequest): StoreDocumentResponse
    {
        Assert::isInstanceOf($storeDocumentRequest, StoreDocumentRequest::class);

        $fileHash = FileHash::make($storeDocumentRequest->content);
        $envelope = $this->cipher->encrypt($storeDocumentRequest->content);
        $documentId = DocumentId::make($this->idGenerator->generate());

        $this->storage->store($documentId, $envelope->payload->value());

        $document = Document::create(
            id: $documentId,
            holderId: HolderId::make('01a17b0e-e559-7fc6-b4e1-4b96eaa3262c'),
            fileHash: $fileHash,
            wrappedDataKey: $envelope->wrappedDataKey,
        );

        $this->documents->add($document);

        return new StoreDocumentResponse(
            id: $documentId->value(),
            holderId: $document->holderId()->value(),
            fileHash: $fileHash->value(),
            wrappedDataKey: $document->wrappedDataKey()->value(),
            createdAt: $document->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
