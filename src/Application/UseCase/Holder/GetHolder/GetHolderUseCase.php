<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolder;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\Holder\InfosHolder;
use App\Domain\ValueObject\Shared\Identifier\HolderId;

/**
 * Lit un titulaire par son identifiant. Remplace la lecture par email, qui
 * plaçait une donnée personnelle dans l'URL (constat M6, issue #86).
 *
 * @implements UseCaseInterface<GetHolderRequest, GetHolderResponse>
 */
final readonly class GetHolderUseCase implements UseCaseInterface
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @throws HolderNotFoundException quand l'identifiant est malformé ou ne correspond à aucun titulaire
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): GetHolderResponse
    {
        // Un identifiant qui n'est même pas un UUID valide ne peut rien désigner : c'est un « non trouvé » (ADR 0013).
        if (!$this->idValidator->isValid($registerIdentityDocumentRequest->id)) {
            throw new HolderNotFoundException();
        }

        $holder = $this->holders->findById(HolderId::make($registerIdentityDocumentRequest->id)) // @phpstan-ignore missingType.checkedException (identifiant validé ci-dessus)
            ?? throw new HolderNotFoundException();

        return new GetHolderResponse(InfosHolder::fromEntity($holder));
    }
}
