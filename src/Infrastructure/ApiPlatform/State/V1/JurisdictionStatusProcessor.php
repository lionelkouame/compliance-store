<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\UpdateJurisdictionStatus\UpdateJurisdictionStatusCommand;
use App\Application\UseCase\UpdateJurisdictionStatus\UpdateJurisdictionStatusUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\JurisdictionResource;

/**
 * @implements ProcessorInterface<JurisdictionResource, JurisdictionResource>
 */
final readonly class JurisdictionStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateJurisdictionStatusUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JurisdictionResource
    {
        $rawCode = $uriVariables['code'] ?? '';
        $code = \is_string($rawCode) ? $rawCode : '';

        $jurisdiction = $this->useCase->execute(new UpdateJurisdictionStatusCommand(
            code: $code,
            active: $data->active,
        ));

        return JurisdictionResource::fromEntity($jurisdiction);
    }
}
