<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class StoreDocumentInput
{
    /**
     * With `deserialize: false`, API Platform never populates these fields
     * itself: DocumentProcessor reads them from the raw request and
     * validates them explicitly against these constraints before use.
     */
    #[Assert\NotNull(message: 'A valid "file" upload is required.')]
    #[Assert\File]
    public ?UploadedFile $file = null;

}
