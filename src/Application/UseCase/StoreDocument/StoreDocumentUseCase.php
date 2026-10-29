<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

use App\Domain\Entity\Document;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\DocumentIdGeneratorInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Holder\HolderId;

final readonly class StoreDocumentUseCase
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private GeneratorInterface $idGenerator,
    ) {}

    public function execute(StoreDocumentCommand $command): Document
    {
        $fileHash = FileHash::fromPlaintext($command->content);
        $envelope = $this->cipher->encrypt($command->content);

        $id = $this->idGenerator->generate();
        $documentId = DocumentId::make($id);

        $this->storage->store($documentId, $envelope->payload->ciphertext);

        $document = Document::create(
            id: $documentId,
            holderId: HolderId::make('01a17b0e-e559-7fc6-b4e1-4b96eaa3262c'),
            fileHash: $fileHash,
            wrappedDataKey: $envelope->wrappedDataKey,
        );

        $this->documents->add($document);

        return $document;
    }
}
