<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

use App\Domain\Entity\Document;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\DocumentIdGeneratorInterface;
use App\Domain\ValueObject\DocumentMetadata;
use App\Domain\ValueObject\FileHash;
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\StorageKey;

final readonly class StoreDocumentUseCase
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private DocumentIdGeneratorInterface $idGenerator,
    ) {}

    public function execute(StoreDocumentCommand $command): Document
    {
        $fileHash = FileHash::fromPlaintext($command->content);
        $envelope = $this->cipher->encrypt($command->content);

        $id = $this->idGenerator->generate();
        $storageKey = StorageKey::forDocument($id);

        $this->storage->store($storageKey, $envelope->payload->ciphertext);

        $document = Document::create(
            id: $id,
            ownerId: new OwnerId($command->ownerId),
            metadata: new DocumentMetadata($command->country, $command->retentionYears, $command->attributes),
            fileHash: $fileHash,
            wrappedDataKey: $envelope->wrappedDataKey,
            storageKey: $storageKey,
        );

        $this->documents->add($document);

        return $document;
    }
}
