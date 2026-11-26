<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Domain\Entity\Document;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Shared\Identifier\DocumentId;
use App\Domain\ValueObject\Shared\Identifier\HolderId;

/**
 * Service applicatif (ADR 0016) : chiffre, stocke et enregistre le contenu
 * d'un document pour le compte de n'importe quel use case qui en produit.
 *
 * Appelé uniquement par des use cases, jamais directement par la présentation.
 */
final readonly class DocumentVault
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private GeneratorInterface $idGenerator,
    ) {}

    /**
     * Le titulaire doit avoir été vérifié par l'appelant.
     *
     * @throws \Throwable when encryption, storage or persistence fails
     */
    public function store(HolderId $holderId, string $content): Document
    {
        $envelope = $this->cipher->encrypt($content);

        $document = Document::create(
            id: DocumentId::make($this->idGenerator->generate()),
            holderId: $holderId,
            fileHash: FileHash::make($content),
            wrappedDataKey: $envelope->wrappedDataKey,
        );

        // Le stockage objet et la base ne partagent pas de transaction : le contenu
        // est écrit d'abord, puis supprimé si l'écriture en base échoue, pour ne
        // jamais laisser de blob chiffré orphelin (review finding M5). L'ordre
        // inverse serait pire : une ligne en base pointant vers aucun contenu.
        $this->storage->store($document->id(), $envelope->payload->value());

        try {
            $this->documents->add($document);
        } catch (\Throwable $failure) {
            $this->removeStoredContent($document->id());

            throw $failure;
        }

        return $document;
    }

    /**
     * Compensation au mieux : un échec du nettoyage est ignoré pour que
     * l'appelant reçoive l'échec d'origine, qui est la vraie cause.
     */
    private function removeStoredContent(DocumentId $documentId): void
    {
        try {
            $this->storage->delete($documentId);
        } catch (\Throwable) {
        }
    }
}
