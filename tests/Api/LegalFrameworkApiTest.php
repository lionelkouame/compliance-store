<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\LegalFramework;
use Doctrine\ORM\EntityManagerInterface;

final class LegalFrameworkApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.LegalFramework::class)->execute();
    }

    public function testItCreatesALegalFrameworkDynamicallyWithoutRedeploy(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'FRAMEWORK-COMMERCIAL-CODE-FR',
                'name' => 'French Commercial Code',
                'officialReference' => 'Commercial Code Art. L123-22',
                'regulatoryAuthority' => 'Ministère de la Justice',
                'jurisdictionCode' => 'JUR-EU-FRA',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        self::assertTrue(\Symfony\Component\Uid\Uuid::isValid($data['id']));
        self::assertJsonContains([
            'code' => 'FRAMEWORK-COMMERCIAL-CODE-FR',
            'name' => 'French Commercial Code',
            'jurisdictionCode' => 'JUR-EU-FRA',
            'active' => true,
        ]);
    }

    public function testItRejectsCreationOfADuplicateCode(): void
    {
        $client = static::createClient();

        $payload = [
            'code' => 'FRAMEWORK-GDPR',
            'name' => 'General Data Protection Regulation (EU 2016/679)',
            'officialReference' => 'OJEU L 119, 4.5.2016',
            'regulatoryAuthority' => 'CNIL / EDPB',
            'jurisdictionCode' => 'JUR-EU-GLOBAL',
        ];

        $client->request('POST', '/api/v1/legal-frameworks', ['json' => $payload]);
        self::assertResponseStatusCodeSame(201);

        // The declarative DTO constraint (AssertLegalFrameworkCodeUnique) catches the
        // duplicate before the Use Case runs, per ADR 0005: HTTP callers get 422
        // (Unprocessable Content); the domain exception (mapped to 409 below) is
        // the second lock reserved for non-HTTP channels (CLI, async workers).
        $client->request('POST', '/api/v1/legal-frameworks', ['json' => $payload]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testItFiltersActiveEuFrameworks(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'FRAMEWORK-GDPR',
                'name' => 'General Data Protection Regulation',
                'officialReference' => 'OJEU L 119, 4.5.2016',
                'regulatoryAuthority' => 'CNIL / EDPB',
                'jurisdictionCode' => 'JUR-EU-GLOBAL',
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'FRAMEWORK-SEC-17A4',
                'name' => 'SEC Rule 17a-4',
                'officialReference' => '17 CFR 240.17a-4',
                'regulatoryAuthority' => 'SEC',
                'jurisdictionCode' => 'JUR-US-CA',
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $response = $client->request('GET', '/api/v1/legal-frameworks', [
            'query' => ['jurisdictionCode' => 'JUR-EU-GLOBAL', 'active' => 'true'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame(1, $data['totalItems']);
        self::assertSame('FRAMEWORK-GDPR', $data['member'][0]['code']);
    }

    public function testItGetsALegalFrameworkByCode(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'FRAMEWORK-GDPR',
                'name' => 'General Data Protection Regulation',
                'officialReference' => 'OJEU L 119, 4.5.2016',
                'regulatoryAuthority' => 'CNIL / EDPB',
                'jurisdictionCode' => 'JUR-EU-GLOBAL',
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/legal-frameworks/FRAMEWORK-GDPR');
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['code' => 'FRAMEWORK-GDPR']);
    }

    public function testItReturns404ForAnUnknownLegalFramework(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/legal-frameworks/FRAMEWORK-DOES-NOT-EXIST');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItRejectsAnInvalidCodeFormat(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'not-a-valid-code',
                'name' => 'Invalid',
                'officialReference' => 'Ref',
                'regulatoryAuthority' => 'Authority',
                'jurisdictionCode' => 'JUR-EU-GLOBAL',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnInvalidJurisdictionCodeFormat(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'FRAMEWORK-GDPR',
                'name' => 'General Data Protection Regulation',
                'officialReference' => 'OJEU L 119, 4.5.2016',
                'regulatoryAuthority' => 'CNIL / EDPB',
                'jurisdictionCode' => 'not-valid',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItDeactivatesALegalFrameworkViaPatch(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/legal-frameworks', [
            'json' => [
                'code' => 'FRAMEWORK-GDPR',
                'name' => 'General Data Protection Regulation',
                'officialReference' => 'OJEU L 119, 4.5.2016',
                'regulatoryAuthority' => 'CNIL / EDPB',
                'jurisdictionCode' => 'JUR-EU-GLOBAL',
            ],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('PATCH', '/api/v1/legal-frameworks/FRAMEWORK-GDPR', [
            'json' => ['active' => false],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['code' => 'FRAMEWORK-GDPR', 'active' => false]);

        $client->request('GET', '/api/v1/legal-frameworks/FRAMEWORK-GDPR');
        self::assertJsonContains(['active' => false]);
    }

    public function testItReturns404WhenPatchingAnUnknownLegalFramework(): void
    {
        $client = static::createClient();

        // The Patch operation's provider resolves existence before reaching the
        // Use Case's own InvalidLegalFrameworkException guard (defense in depth
        // for non-HTTP callers, see ADR 0005).
        $client->request('PATCH', '/api/v1/legal-frameworks/FRAMEWORK-DOES-NOT-EXIST', [
            'json' => ['active' => false],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
