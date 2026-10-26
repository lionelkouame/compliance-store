<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListHolderDocuments\ListHolderDocumentsUseCase;
use App\Domain\Entity\Document;
use App\Domain\Exception\HolderNotFoundException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\DocumentResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<DocumentResource>
 */
final readonly class ListHolderDocumentsProvider implements ProviderInterface
{
    public function __construct(
        private ListHolderDocumentsUseCase $useCase,
    ) {}

    /**
     * @return list<DocumentResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $holderId = $uriVariables['holderId'] ?? null;

        if (!\is_string($holderId)) {
            throw new NotFoundHttpException('Invalid holder id.');
        }

        try {
            $documents = $this->useCase->execute($holderId);
        } catch (HolderNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Invalid holder id.', $e);
        }

        return array_map(
            static fn (Document $document): DocumentResource => DocumentResource::fromEntity($document),
            $documents,
        );
    }
}
