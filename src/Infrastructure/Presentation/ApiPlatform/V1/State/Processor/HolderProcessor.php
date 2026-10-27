<?php

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\CreateHolder\CreateHolderCommand;
use App\Application\UseCase\CreateHolder\CreateHolderUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\DocumentResource;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\HolderResource;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProcessorInterface<mixed, DocumentResource>
 */
final readonly class HolderProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
        private CreateHolderUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = json_decode($request?->getContent() ?? '', true);
        $payload = \is_array($payload) ? $payload : [];

        $input = new HolderResource();
        $input->holderName = \is_string($payload['holderName'] ?? null) ? $payload['holderName'] : null;
        $input->birthDate = \is_string($payload['birthDate'] ?? null) ? $payload['birthDate'] : null;
        $input->email = \is_string($payload['email'] ?? null) ? $payload['email'] : null;

        $this->validator->validate($input);


        try {
            $command = new CreateHolderCommand(
                holderName: $input->holderName,
                birthDate: $input->birthDate,
                email: $input->email,
            );
            $this->useCase->execute($command);
        }
        catch (\InvalidArgumentException $e) {

        }

        return HolderResource::fromEntity($input);
    }

}
