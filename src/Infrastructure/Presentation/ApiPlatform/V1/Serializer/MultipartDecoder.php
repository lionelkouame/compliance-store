<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Serializer;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Lets API Platform deserialize `multipart/form-data` requests: the form
 * fields and uploaded files of the current request become the input data,
 * then denormalized into the operation's input DTO.
 */
final readonly class MultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        return array_map(
            static fn (mixed $element): mixed => \is_string($element) ? (json_decode($element, true) ?? $element) : $element,
            $request->request->all(),
        ) + $request->files->all();
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
