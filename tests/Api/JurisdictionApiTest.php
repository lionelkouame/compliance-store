<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Jurisdiction;
use Doctrine\ORM\EntityManagerInterface;

final class JurisdictionApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Jurisdiction::class)->execute();
    }

    public function testItCreatesAJurisdictionDynamicallyWithoutRedeploy(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-EU-FRA',
                'label' => 'France (European Union)',
                'region' => 'EU',
                'country' => 'FRA',
                'subRegion' => null,
                'applicableFrameworks' => ['GDPR', 'EIDAS_2', 'COMMERCIAL_CODE_FR'],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        self::assertTrue(\Symfony\Component\Uid\Uuid::isValid($data['id']));
        self::assertJsonContains([
            'code' => 'JUR-EU-FRA',
            'label' => 'France (European Union)',
            'region' => 'EU',
            'country' => 'FRA',
            'applicableFrameworks' => ['GDPR', 'EIDAS_2', 'COMMERCIAL_CODE_FR'],
            'active' => true,
        ]);
    }

    public function testItRejectsCreationOfADuplicateCode(): void
    {
        $client = static::createClient();

        $payload = [
            'code' => 'JUR-EU-FRA',
            'label' => 'France (European Union)',
            'region' => 'EU',
            'country' => 'FRA',
            'applicableFrameworks' => ['GDPR'],
        ];

        $client->request('POST', '/api/v1/jurisdictions', ['json' => $payload]);
        self::assertResponseStatusCodeSame(201);

        // The declarative DTO constraint (AssertJurisdictionCodeUnique) catches the
        // duplicate before the Use Case runs, per ADR 0005: HTTP callers get 422
        // (Unprocessable Content); the domain exception (mapped to 409 below) is
        // the second lock reserved for non-HTTP channels (CLI, async workers).
        $client->request('POST', '/api/v1/jurisdictions', ['json' => $payload]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testItFiltersActiveEuropeanJurisdictions(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-EU-FRA',
                'label' => 'France (European Union)',
                'region' => 'EU',
                'country' => 'FRA',
                'applicableFrameworks' => ['GDPR'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-EU-DEU',
                'label' => 'Germany (European Union)',
                'region' => 'EU',
                'country' => 'DEU',
                'applicableFrameworks' => ['GDPR'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-US-CA',
                'label' => 'California (United States)',
                'region' => 'NA',
                'country' => 'USA',
                'subRegion' => 'CA',
                'applicableFrameworks' => ['CCPA'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $response = $client->request('GET', '/api/v1/jurisdictions', [
            'query' => ['region' => 'EU', 'active' => 'true'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame(2, $data['totalItems']);

        $codes = array_map(static fn (array $item): string => $item['code'], $data['member']);
        sort($codes);
        self::assertSame(['JUR-EU-DEU', 'JUR-EU-FRA'], $codes);
    }

    public function testItGetsAJurisdictionByCode(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-EU-FRA',
                'label' => 'France (European Union)',
                'region' => 'EU',
                'country' => 'FRA',
                'applicableFrameworks' => ['GDPR'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/jurisdictions/JUR-EU-FRA');
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['code' => 'JUR-EU-FRA']);
    }

    public function testItReturns404ForAnUnknownJurisdiction(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/jurisdictions/JUR-DOES-NOT-EXIST');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItRejectsAnInvalidCodeFormat(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'not-a-valid-code',
                'label' => 'Invalid',
                'region' => 'EU',
                'applicableFrameworks' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnInvalidCountryFormat(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-EU-FRA',
                'label' => 'France',
                'region' => 'EU',
                'country' => 'FR',
                'applicableFrameworks' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItDeactivatesAJurisdictionViaPatch(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/jurisdictions', [
            'json' => [
                'code' => 'JUR-EU-FRA',
                'label' => 'France (European Union)',
                'region' => 'EU',
                'country' => 'FRA',
                'applicableFrameworks' => ['GDPR'],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('PATCH', '/api/v1/jurisdictions/JUR-EU-FRA', [
            'json' => ['active' => false],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['code' => 'JUR-EU-FRA', 'active' => false]);

        $client->request('GET', '/api/v1/jurisdictions/JUR-EU-FRA');
        self::assertJsonContains(['active' => false]);
    }

    public function testItReturns404WhenPatchingAnUnknownJurisdiction(): void
    {
        $client = static::createClient();

        $client->request('PATCH', '/api/v1/jurisdictions/JUR-DOES-NOT-EXIST', [
            'json' => ['active' => false],
        ]);

        // The Patch operation's provider resolves existence before reaching the
        // Use Case's own InvalidJurisdictionException guard (defense in depth
        // for non-HTTP callers, see ADR 0005).
        self::assertResponseStatusCodeSame(404);
    }
}
