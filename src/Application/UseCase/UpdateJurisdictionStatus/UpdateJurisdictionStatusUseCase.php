<?php

declare(strict_types=1);

namespace App\Application\UseCase\UpdateJurisdictionStatus;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Exception\InvalidJurisdictionException;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;

final readonly class UpdateJurisdictionStatusUseCase
{
    public function __construct(
        private JurisdictionRepositoryInterface $jurisdictions,
    ) {}

    /**
     * @throws InvalidJurisdictionException if the code is unknown
     */
    public function execute(UpdateJurisdictionStatusCommand $command): Jurisdiction
    {
        if (!JurisdictionCode::isValid($command->code)) {
            throw InvalidJurisdictionException::forCode($command->code);
        }

        $jurisdiction = $this->jurisdictions->findByCode(new JurisdictionCode($command->code));

        if (null === $jurisdiction) {
            throw InvalidJurisdictionException::forCode($command->code);
        }

        if ($command->active) {
            $jurisdiction->activate();
        } else {
            $jurisdiction->deactivate();
        }

        $this->jurisdictions->update($jurisdiction);

        return $jurisdiction;
    }
}
