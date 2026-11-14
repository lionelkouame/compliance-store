<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateStorageSpaceInput
{
    #[Assert\NotBlank(message: 'A storage space code is required.')]
    public ?string $code = null;

    #[Assert\NotBlank(message: 'A storage space name is required.')]
    public ?string $name = null;
}
