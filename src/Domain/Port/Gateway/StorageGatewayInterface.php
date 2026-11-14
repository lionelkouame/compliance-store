<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use App\Domain\ValueObject\Document\DocumentId;

/**
 * Zero Trust Storage port (ADR 0002): only ever receives already-encrypted
 * bytes. The physical storage key is a backend concern (ADR 0009) derived
 * from the {@see DocumentId} inside the adapter, never exposed to the domain.
 */
interface StorageGatewayInterface
{
    public function store(DocumentId $id, string $ciphertext): void;

    public function read(DocumentId $id): string;

    /**
     * Removes a document's content. Deleting content that does not exist is
     * not an error, so the call is safe to repeat (compensation, retries).
     */
    public function delete(DocumentId $id): void;
}
