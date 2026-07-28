<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\InvalidRegulatoryScopeException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;

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
     * @throws InvalidRegulatoryScopeException si le périmètre est inactif, mal formé ou inconnu
     */
    public function assertActive(string $code): RegulatoryScope
    {
        try {
            $scopeCode = new RegulatoryScopeCode($code);
        } catch (\InvalidArgumentException) {
            throw InvalidRegulatoryScopeException::forCode($code);
        }

        $scope = $this->regulatoryScopes->findActiveByCode($scopeCode);

        if (null === $scope) {
            throw InvalidRegulatoryScopeException::forCode($code);
        }

        return $scope;
    }
}
