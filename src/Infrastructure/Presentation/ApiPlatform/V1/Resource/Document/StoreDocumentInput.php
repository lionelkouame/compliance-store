<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class StoreDocumentInput
{
    #[Assert\NotNull(message: 'A valid "file" upload is required.')]
    #[Assert\File]
    public ?UploadedFile $file = null;
}
