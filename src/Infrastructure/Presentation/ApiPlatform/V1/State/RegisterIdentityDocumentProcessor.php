<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\RegisterIdentityDocument\RegisterIdentityDocumentCommand;
use App\Application\UseCase\RegisterIdentityDocument\RegisterIdentityDocumentUseCase;
use App\Domain\Exception\IdentityDocumentAlreadyExistsException;
use App\Domain\Exception\IdentityDocumentExpiredException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\IdentityDocumentResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<mixed, IdentityDocumentResource>
 */
final readonly class RegisterIdentityDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private RegisterIdentityDocumentUseCase $useCase,
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IdentityDocumentResource
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = json_decode($request?->getContent() ?? '', true);
        $payload = \is_array($payload) ? $payload : [];

        $input = new IdentityDocumentResource();
        $input->type = \is_string($payload['type'] ?? null) ? $payload['type'] : null;
        $input->country = \is_string($payload['country'] ?? null) ? $payload['country'] : null;
        $input->number = \is_string($payload['number'] ?? null) ? $payload['number'] : null;
        $input->expiresAt = \is_string($payload['expiresAt'] ?? null) ? $payload['expiresAt'] : null;

        $this->validator->validate($input);

        if (null === $input->type || null === $input->country || null === $input->number || null === $input->expiresAt) {
            throw new UnprocessableEntityHttpException('Missing required identity document parameters.');
        }

        try {
            $identityDocument = $this->useCase->execute(new RegisterIdentityDocumentCommand(
                type: $input->type,
                country: $input->country,
                number: $input->number,
                expiresAt: $input->expiresAt,
            ));
        } catch (IdentityDocumentAlreadyExistsException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } catch (IdentityDocumentExpiredException|\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return IdentityDocumentResource::fromEntity($identityDocument);
    }
}
