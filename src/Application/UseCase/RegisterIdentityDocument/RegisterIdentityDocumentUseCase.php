<?php

declare(strict_types=1);

namespace App\Application\UseCase\RegisterIdentityDocument;

use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocumentAlreadyExistsException;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\IdentityDocumentNumber;
use App\Domain\ValueObject\IdentityDocumentType;

final readonly class RegisterIdentityDocumentUseCase
{
    public function __construct(
        private IdentityDocumentRepositoryInterface $identityDocuments,
        private IdentityDocumentIdGeneratorInterface $idGenerator,
    ) {}

    public function execute(RegisterIdentityDocumentCommand $command): IdentityDocument
    {
        $type = IdentityDocumentType::tryFrom($command->type) ?? throw new \InvalidArgumentException(\sprintf(
            'The identity document type "%s" must be one of: %s.',
            $command->type,
            implode(', ', array_column(IdentityDocumentType::cases(), 'value')),
        ));
        $country = new CountryCode($command->country);
        $number = new IdentityDocumentNumber($command->number);

        if (null !== $this->identityDocuments->findByNumber($type, $country, $number)) {
            throw new IdentityDocumentAlreadyExistsException($type, $country, $number);
        }

        $identityDocument = IdentityDocument::create(
            id: $this->idGenerator->generate(),
            type: $type,
            country: $country,
            number: $number,
            expiresAt: $this->parseDate($command->expiresAt),
        );

        $this->identityDocuments->add($identityDocument);

        return $identityDocument;
    }

    private function parseDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));

        // createFromFormat silently rolls over impossible dates (2030-02-30), so re-format to reject them.
        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException(\sprintf('The expiry date "%s" must be a valid Y-m-d date.', $value));
        }

        return $date;
    }
}
