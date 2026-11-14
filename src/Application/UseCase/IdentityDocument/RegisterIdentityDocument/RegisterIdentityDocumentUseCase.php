<?php

declare(strict_types=1);

namespace App\Application\UseCase\IdentityDocument\RegisterIdentityDocument;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocument\IdentityDocumentAlreadyExistsException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ReadModel\IdentityDocument\InfosIdentityDocument;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
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
        $type = IdentityDocumentType::tryFrom($request->type) ?? throw new InvalidValueException(\sprintf(
            'The identity document type "%s" must be one of: %s.',
            $request->type,
            implode(', ', array_column(IdentityDocumentType::cases(), 'value')),
        ));
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
            expiresAt: $this->parseDate($request->expiresAt),
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

    private function parseDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));

        // createFromFormat silently rolls over impossible dates (2030-02-30), so re-format to reject them.
        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new InvalidValueException(\sprintf('The expiry date "%s" must be a valid Y-m-d date.', $value));
        }

        return $date;
    }
}
