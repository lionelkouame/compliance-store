<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\NonCompliantDocumentException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\DocumentType;

/**
 * Native Compliance Core (ADR 0001): a document is only accepted if its type
 * is covered by an active regulatory scope. Fail-closed.
 */
final readonly class DocumentComplianceChecker
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    /**
     * @throws NonCompliantDocumentException if no active regulatory scope covers this document type
     */
    public function assertCompliant(DocumentType $documentType): RegulatoryScope
    {
        $scope = $this->regulatoryScopes->findActiveByAllowedDocumentType($documentType);

        if (null === $scope) {
            throw NonCompliantDocumentException::forDocumentType($documentType->value);
        }

        return $scope;
    }
}
