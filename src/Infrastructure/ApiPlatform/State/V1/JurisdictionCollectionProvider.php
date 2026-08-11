<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListJurisdictions\ListJurisdictionsQuery;
use App\Application\UseCase\ListJurisdictions\ListJurisdictionsUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\JurisdictionResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProviderInterface<JurisdictionResource>
 */
final readonly class JurisdictionCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListJurisdictionsUseCase $useCase,
    ) {}

    /**
     * @param array{request?: Request} $context
     * @return list<JurisdictionResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $query = isset($context['request']) ? $this->buildQueryFromRequest($context['request']) : new ListJurisdictionsQuery();

        return array_map(
            JurisdictionResource::fromEntity(...),
            $this->useCase->execute($query),
        );
    }

    private function buildQueryFromRequest(Request $request): ListJurisdictionsQuery
    {
        $region = $request->query->get('region');
        $country = $request->query->get('country');
        $active = $request->query->get('active');

        return new ListJurisdictionsQuery(
            region: \is_string($region) ? $region : null,
            country: \is_string($country) ? $country : null,
            active: null !== $active ? filter_var($active, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE) : null,
        );
    }
}
