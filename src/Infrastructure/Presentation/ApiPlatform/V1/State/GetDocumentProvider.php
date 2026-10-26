<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetDocument\GetDocumentUseCase;
use App\Domain\Exception\DocumentNotFoundException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\DocumentResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<DocumentResource>
 */
final readonly class GetDocumentProvider implements ProviderInterface
{
    public function __construct(
        private GetDocumentUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DocumentResource
    {
        $id = $uriVariables['id'] ?? null;

        if (!\is_string($id)) {
            throw new NotFoundHttpException('Invalid document id.');
        }

        try {
            $document = $this->useCase->execute($id);
        } catch (DocumentNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Invalid document id.', $e);
        }

        return DocumentResource::fromEntity($document);
    }
}
