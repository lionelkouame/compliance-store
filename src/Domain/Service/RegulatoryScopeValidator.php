<?php

namespace App\Domain\Service;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\InvalidRegulatoryScopeException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;

/**
 * Vérifie dynamiquement qu'un code de périmètre réglementaire est connu et actif
 * avant qu'il ne soit associé à un document (moteur de règles).
 */
final readonly class RegulatoryScopeValidator
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    /**
     * @throws InvalidRegulatoryScopeException si le périmètre est inactif ou inconnu
     */
    public function assertActive(string $code): RegulatoryScope
    {
        $scope = $this->regulatoryScopes->findActiveByCode($code);

        if (null === $scope) {
            throw InvalidRegulatoryScopeException::forCode($code);
        }

        return $scope;
    }
}
