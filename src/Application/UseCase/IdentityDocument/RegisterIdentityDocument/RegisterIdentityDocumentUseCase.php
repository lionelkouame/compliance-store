<?php

declare(strict_types=1);

namespace App\Application\UseCase\IdentityDocument\RegisterIdentityDocument;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocument\IdentityDocumentAlreadyExistsException;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ReadModel\IdentityDocument\InfosIdentityDocument;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\IdentityDocument\ExpiryDate;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;

/**
 * @implements UseCaseInterface<RegisterIdentityDocumentRequest, RegisterIdentityDocumentResponse>
 */
final readonly class RegisterIdentityDocumentUseCase implements UseCaseInterface
{
    public function __construct(
        private IdentityDocumentRepositoryInterface $identityDocuments,
        private IdentityDocumentIdGeneratorInterface $idGenerator,
    ) {}

    public function execute(UseCaseRequestInterface $request): RegisterIdentityDocumentResponse
    {
        $type = IdentityDocumentType::fromString($request->type);
        $country = new CountryCode($request->country);
        $number = new IdentityDocumentNumber($request->number);

        if (null !== $this->identityDocuments->findByNumber($type, $country, $number)) {
            throw new IdentityDocumentAlreadyExistsException($type, $country, $number);
        }

        $identityDocument = IdentityDocument::create(
            id: $this->idGenerator->generate(),
            type: $type,
            country: $country,
            number: $number,
            expiresAt: ExpiryDate::make($request->expiresAt),
        );

        $this->identityDocuments->add($identityDocument);

        return new RegisterIdentityDocumentResponse(new InfosIdentityDocument(
            id: $identityDocument->id(),
            type: $identityDocument->type(),
            country: $identityDocument->country(),
            number: $identityDocument->number(),
            expiresAt: $identityDocument->expiresAt(),
            createdAt: $identityDocument->createdAt(),
        ));
    }
}
