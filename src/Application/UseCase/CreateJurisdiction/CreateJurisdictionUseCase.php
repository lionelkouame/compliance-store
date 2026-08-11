<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateJurisdiction;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Exception\JurisdictionAlreadyExistsException;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\Port\Service\JurisdictionIdGeneratorInterface;
use App\Domain\ValueObject\ApplicableFrameworks;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionCountry;
use App\Domain\ValueObject\JurisdictionLabel;
use App\Domain\ValueObject\JurisdictionRegion;
use App\Domain\ValueObject\JurisdictionSubRegion;

final readonly class CreateJurisdictionUseCase
{
    public function __construct(
        private JurisdictionRepositoryInterface $jurisdictions,
        private JurisdictionIdGeneratorInterface $idGenerator,
    ) {}

    /**
     * @throws JurisdictionAlreadyExistsException if the code already exists
     */
    public function execute(CreateJurisdictionCommand $command): Jurisdiction
    {
        $code = new JurisdictionCode($command->code);

        if ($this->jurisdictions->existsByCode($code)) {
            throw JurisdictionAlreadyExistsException::forCode($command->code);
        }

        $id = $this->idGenerator->generate();

        $jurisdiction = Jurisdiction::create(
            id: $id,
            code: $code,
            label: new JurisdictionLabel($command->label),
            region: new JurisdictionRegion($command->region),
            country: null !== $command->country ? new JurisdictionCountry($command->country) : null,
            subRegion: null !== $command->subRegion ? new JurisdictionSubRegion($command->subRegion) : null,
            applicableFrameworks: ApplicableFrameworks::fromStrings(...$command->applicableFrameworks),
            active: $command->active,
        );

        $this->jurisdictions->add($jurisdiction);

        return $jurisdiction;
    }
}
