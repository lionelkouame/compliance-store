<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace;

use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;

/**
 * Shared by every StorageSpace operation: they all expose the same representation.
 */
final readonly class StorageSpaceOutput
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromReadModel(InfosStorageSpace $readModel): self
    {
        return new self(
            id: $readModel->id->value,
            code: $readModel->code->value,
            name: $readModel->name->value,
            status: $readModel->status->value,
            createdAt: $readModel->createdAt->format(\DateTimeInterface::ATOM),
        );
    }
}
