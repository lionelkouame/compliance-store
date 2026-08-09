<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use App\Domain\ValueObject\StorageKey;

/**
 * Zero Trust Storage port (ADR 0002): only ever receives already-encrypted
 * bytes.
 */
interface StorageGatewayInterface
{
    public function store(StorageKey $key, string $ciphertext): void;

    public function read(StorageKey $key): string;
}
