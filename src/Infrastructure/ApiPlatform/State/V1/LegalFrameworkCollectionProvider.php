<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListLegalFrameworks\ListLegalFrameworksQuery;
use App\Application\UseCase\ListLegalFrameworks\ListLegalFrameworksUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\LegalFrameworkResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProviderInterface<LegalFrameworkResource>
 */
final readonly class LegalFrameworkCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListLegalFrameworksUseCase $useCase,
    ) {}

    /**
     * @param array{request?: Request} $context
     * @return list<LegalFrameworkResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $query = isset($context['request']) ? $this->buildQueryFromRequest($context['request']) : new ListLegalFrameworksQuery();

        return array_map(
            LegalFrameworkResource::fromEntity(...),
            $this->useCase->execute($query),
        );
    }

    private function buildQueryFromRequest(Request $request): ListLegalFrameworksQuery
    {
        $jurisdictionCode = $request->query->get('jurisdictionCode');
        $active = $request->query->get('active');

        return new ListLegalFrameworksQuery(
            jurisdictionCode: \is_string($jurisdictionCode) ? $jurisdictionCode : null,
            active: null !== $active ? filter_var($active, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE) : null,
        );
    }
}
