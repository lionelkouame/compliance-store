<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Serializer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Passes uploaded files decoded by {@see MultipartDecoder} through as-is, so
 * they can be assigned to `UploadedFile` properties of input DTOs.
 */
final class UploadedFileDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): File
    {
        \assert($data instanceof File);

        return $data;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $data instanceof File;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [File::class => true];
    }
}
