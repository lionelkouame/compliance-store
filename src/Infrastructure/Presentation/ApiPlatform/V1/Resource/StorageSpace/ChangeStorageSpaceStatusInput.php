<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeStorageSpaceStatusInput
{
    #[Assert\NotBlank(message: 'A "status" field is required.')]
    public ?string $status = null;
}
