<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use App\Domain\ValueObject\DocumentId;

/**
 * Zero Trust Storage port (ADR 0002): only ever receives already-encrypted
 * bytes. The physical storage key is a backend concern (ADR 0009) derived
 * from the {@see DocumentId} inside the adapter, never exposed to the domain.
 */
interface StorageGatewayInterface
{
    public function store(DocumentId $id, string $ciphertext): void;

    public function read(DocumentId $id): string;
}
