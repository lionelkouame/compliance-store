<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ConsultDocument\ConsultDocumentUseCase;
use App\Domain\Exception\DocumentIntegrityException;
use App\Domain\Exception\DocumentNotFoundException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the decrypted bytes of a document as a download (ADR 0011: a state
 * provider returning a Response, no controller).
 *
 * @implements ProviderInterface<Response>
 */
final readonly class GetDocumentContentProvider implements ProviderInterface
{
    public function __construct(
        private ConsultDocumentUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        $id = $uriVariables['id'] ?? null;

        if (!\is_string($id)) {
            throw new NotFoundHttpException('Invalid document id.');
        }

        try {
            $consulted = $this->useCase->execute($id);
        } catch (DocumentNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Invalid document id.', $e);
        } catch (DocumentIntegrityException $e) {
            // The stored data is unusable: say so without releasing anything.
            throw new HttpException(Response::HTTP_CONFLICT, $e->getMessage(), $e);
        } catch (\RuntimeException $e) {
            // Storage or cipher failure: the message may name a bucket or a key, keep it for the logs only.
            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'The document content is unavailable.', $e);
        }

        return new Response($consulted->content, Response::HTTP_OK, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) \strlen($consulted->content),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $consulted->document->id()->value.'.bin'),
            // Decrypted personal data: never cache it anywhere.
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Document-Hash' => $consulted->document->fileHash()->value,
        ]);
    }
}
