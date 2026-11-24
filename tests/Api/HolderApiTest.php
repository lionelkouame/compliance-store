<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Holder;
use Doctrine\ORM\EntityManagerInterface;

final class HolderApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const HOLDER = [
        'holderName' => 'Jane Doe',
        'birthDate' => '1990-01-01',
        'email' => 'jane@example.com',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
    }

    public function testItCreatesAHolder(): void
    {
        $data = static::createClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::HOLDER,
        ])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertNotEmpty($data['identifier']);
    }

    public function testItRejectsMissingFields(): void
    {
        static::createClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsABirthDateInTheFuture(): void
    {
        static::createClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['birthDate' => '2999-01-01'] + self::HOLDER,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsADuplicateEmail(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::HOLDER,
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::HOLDER,
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testItGetsAHolderById(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::HOLDER,
        ])->toArray();

        $data = $client->request('GET', '/api/v1/holders/'.$created['identifier'])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created['identifier'], $data['holderId']);
        self::assertSame('Jane Doe', $data['holderName']);
        self::assertSame('1990-01-01', $data['birthDate']);
        self::assertSame('active', $data['status']);
        self::assertSame('jane@example.com', $data['email']);
    }

    public function testItReturns404ForAnUnknownId(): void
    {
        static::createClient()->request('GET', '/api/v1/holders/01a17b0e-e559-7fc6-b4e1-4b96eaa3262c');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItReturns404ForAMalformedId(): void
    {
        static::createClient()->request('GET', '/api/v1/holders/not-a-uuid');

        self::assertResponseStatusCodeSame(404);
    }

    public function testTheEmailCanNoLongerBeUsedInTheUrl(): void
    {
        // Constat M6 : une donnée personnelle ne doit plus jamais servir de clé dans une URL.
        static::createClient()->request('GET', '/api/v1/holders/jane@example.com');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItSearchesAHolderByEmail(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::HOLDER,
        ])->toArray();

        $data = $client->request('POST', '/api/v1/holders/search', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['email' => 'jane@example.com'],
        ])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created['identifier'], $data['holderId']);
        self::assertSame('jane@example.com', $data['email']);
    }

    public function testSearchReturns404ForAnUnknownEmail(): void
    {
        static::createClient()->request('POST', '/api/v1/holders/search', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['email' => 'nobody@example.com'],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testSearchRejectsAMissingOrInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/holders/search', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);
        self::assertResponseStatusCodeSame(422);

        $client->request('POST', '/api/v1/holders/search', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['email' => 'not-an-email'],
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAMissingEmail(): void
    {
        $response = static::createClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Jane Doe', 'birthDate' => '1990-01-01'],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringNotContainsString('Expected a string', $response->getContent(false));
    }

    public function testItRejectsAnInvalidEmail(): void
    {
        static::createClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['email' => 'not-an-email'] + self::HOLDER,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnImpossibleBirthDate(): void
    {
        static::createClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['birthDate' => '1990-02-30'] + self::HOLDER,
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
