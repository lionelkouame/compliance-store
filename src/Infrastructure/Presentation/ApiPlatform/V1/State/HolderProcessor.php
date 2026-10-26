<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\CreateHolder\CreateHolderCommand;
use App\Application\UseCase\CreateHolder\CreateHolderUseCase;
use App\Domain\Exception\HolderAlreadyExistsException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\HolderResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<mixed, HolderResource>
 */
final readonly class HolderProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateHolderUseCase $useCase,
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HolderResource
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = json_decode($request?->getContent() ?? '', true);
        $payload = \is_array($payload) ? $payload : [];

        $input = new HolderResource();
        $input->holderName = \is_string($payload['holderName'] ?? null) ? $payload['holderName'] : null;
        $input->birthDate = \is_string($payload['birthDate'] ?? null) ? $payload['birthDate'] : null;
        $input->email = \is_string($payload['email'] ?? null) ? $payload['email'] : null;

        $this->validator->validate($input);

        if (null === $input->holderName || null === $input->birthDate || null === $input->email) {
            throw new UnprocessableEntityHttpException('Missing required holder parameters.');
        }

        try {
            $holder = $this->useCase->execute(new CreateHolderCommand(
                holderName: $input->holderName,
                birthDate: $input->birthDate,
                email: $input->email,
            ));
        } catch (HolderAlreadyExistsException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return HolderResource::fromEntity($holder);
    }
}
