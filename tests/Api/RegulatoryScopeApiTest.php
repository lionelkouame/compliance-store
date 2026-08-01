<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\InvalidRegulatoryScopeException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\Service\RegulatoryScopeValidator;
use App\Domain\ValueObject\AllowedDocumentTypes;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeDescription;
use App\Domain\ValueObject\RegulatoryScopeLabel;
use Doctrine\ORM\EntityManagerInterface;

final class RegulatoryScopeApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.RegulatoryScope::class)->execute();
    }

    public function testItCreatesARegulatoryScopeDynamicallyWithoutRedeploy(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/v1/regulatory-scopes', [
            'json' => [
                'code' => 'CREDIT_AUDIT',
                'label' => "Audit de solvabilité crédit",
                'description' => 'Nouveau cas d\'usage métier ajouté à la volée',
                'allowedDocumentTypes' => ['BANK_STATEMENT', 'PAYSLIP'],
                'isActive' => true,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $id = $data['id'] ?? basename($data['@id'] ?? '');
        self::assertTrue(\Symfony\Component\Uid\Uuid::isValid($id));
        self::assertJsonContains([
            'code' => 'CREDIT_AUDIT',
            'label' => "Audit de solvabilité crédit",
            'allowedDocumentTypes' => ['BANK_STATEMENT', 'PAYSLIP'],
            'isActive' => true,
        ]);

        // Consultable par UUID ou par code
        $client->request('GET', '/api/v1/regulatory-scopes/'.$id);
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['code' => 'CREDIT_AUDIT']);

        $client->request('GET', '/api/v1/regulatory-scopes/CREDIT_AUDIT');
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['code' => 'CREDIT_AUDIT']);
    }

    public function testItListsRegulatoryScopes(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/regulatory-scopes', [
            'json' => [
                'code' => 'KYC_INDIVIDUAL',
                'label' => "Vérification d'Identité Particulier",
                'description' => 'KYC',
                'allowedDocumentTypes' => ['PASSPORT', 'NATIONAL_ID'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/regulatory-scopes');

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['totalItems' => 1]);
    }

    public function testItReturns404ForAnUnknownScope(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/regulatory-scopes/DOES_NOT_EXIST');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItRejectsCreationOfADuplicateCode(): void
    {
        $client = static::createClient();

        $payload = [
            'code' => 'GDPR_RETENTION',
            'label' => 'Rétention RGPD',
            'description' => 'Durée de conservation',
            'allowedDocumentTypes' => [],
        ];

        $client->request('POST', '/api/v1/regulatory-scopes', ['json' => $payload]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/regulatory-scopes', ['json' => $payload]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnInvalidCodeFormat(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/regulatory-scopes', [
            'json' => [
                'code' => 'not-a-valid-code',
                'label' => 'Invalide',
                'description' => '',
                'allowedDocumentTypes' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testValidatorRejectsAnInactiveScope(): void
    {
        self::bootKernel();

        $repository = self::getContainer()->get(RegulatoryScopeRepositoryInterface::class);
        $validator = self::getContainer()->get(RegulatoryScopeValidator::class);

        $idGenerator = self::getContainer()->get(\App\Domain\Port\Service\IdGeneratorInterface::class);

        $scope = RegulatoryScope::create(
            id: $idGenerator->generate(),
            code: new RegulatoryScopeCode('OLD_SCOPE'),
            label: new RegulatoryScopeLabel('Ancien périmètre'),
            description: new RegulatoryScopeDescription('Désactivé'),
            allowedDocumentTypes: new AllowedDocumentTypes(),
            isActive: false,
        );
        $repository->add($scope);

        $this->expectException(InvalidRegulatoryScopeException::class);

        $validator->assertActive('OLD_SCOPE');
    }

    public function testValidatorRejectsAnUnknownScope(): void
    {
        self::bootKernel();

        $validator = self::getContainer()->get(RegulatoryScopeValidator::class);

        $this->expectException(InvalidRegulatoryScopeException::class);

        $validator->assertActive('UNKNOWN_SCOPE');
    }
}
