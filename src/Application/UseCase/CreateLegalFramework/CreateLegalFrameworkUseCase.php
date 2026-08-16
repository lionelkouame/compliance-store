<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateLegalFramework;

use App\Domain\Entity\LegalFramework;
use App\Domain\Exception\LegalFrameworkAlreadyExistsException;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\Port\Service\LegalFrameworkIdGeneratorInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkName;
use App\Domain\ValueObject\LegalFrameworkOfficialReference;
use App\Domain\ValueObject\LegalFrameworkRegulatoryAuthority;

final readonly class CreateLegalFrameworkUseCase
{
    public function __construct(
        private LegalFrameworkRepositoryInterface $legalFrameworks,
        private LegalFrameworkIdGeneratorInterface $idGenerator,
    ) {}

    /**
     * @throws LegalFrameworkAlreadyExistsException if the code already exists
     */
    public function execute(CreateLegalFrameworkCommand $command): LegalFramework
    {
        $code = new LegalFrameworkCode($command->code);

        if ($this->legalFrameworks->existsByCode($code)) {
            throw LegalFrameworkAlreadyExistsException::forCode($command->code);
        }

        $id = $this->idGenerator->generate();

        $legalFramework = LegalFramework::create(
            id: $id,
            code: $code,
            name: new LegalFrameworkName($command->name),
            officialReference: new LegalFrameworkOfficialReference($command->officialReference),
            regulatoryAuthority: new LegalFrameworkRegulatoryAuthority($command->regulatoryAuthority),
            jurisdictionCode: new JurisdictionCode($command->jurisdictionCode),
            active: $command->active,
        );

        $this->legalFrameworks->add($legalFramework);

        return $legalFramework;
    }
}
