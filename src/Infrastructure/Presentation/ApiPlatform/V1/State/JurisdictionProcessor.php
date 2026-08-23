<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\CreateJurisdiction\CreateJurisdictionCommand;
use App\Application\UseCase\CreateJurisdiction\CreateJurisdictionUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\JurisdictionResource;

/**
 * @implements ProcessorInterface<JurisdictionResource, JurisdictionResource>
 */
final readonly class JurisdictionProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateJurisdictionUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JurisdictionResource
    {
        $jurisdiction = $this->useCase->execute(new CreateJurisdictionCommand(
            code: (string) $data->code,
            label: (string) $data->label,
            region: (string) $data->region,
            country: $data->country,
            subRegion: $data->subRegion,
            applicableFrameworks: $data->applicableFrameworks,
            active: $data->active,
        ));

        return JurisdictionResource::fromEntity($jurisdiction);
    }
}
