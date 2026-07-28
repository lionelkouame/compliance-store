<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\InvalidRegulatoryScopeException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;

/**
 * Dynamically verifies that a regulatory scope code is known and active
 * before it is associated with a document (rules engine).
 */
final readonly class RegulatoryScopeValidator
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    /**
     * @throws InvalidRegulatoryScopeException if the scope is inactive, malformed, or unknown
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
