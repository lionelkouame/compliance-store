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

    public function testItGetsAHolderByEmail(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::HOLDER,
        ])->toArray();

        $data = $client->request('GET', '/api/v1/holders/jane@example.com')->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created['identifier'], $data['holderId']);
        self::assertSame('Jane Doe', $data['holderName']);
        self::assertSame('1990-01-01', $data['birthDate']);
        self::assertSame('active', $data['status']);
        self::assertSame('jane@example.com', $data['email']);
    }

    public function testItReturns404ForAnUnknownEmail(): void
    {
        static::createClient()->request('GET', '/api/v1/holders/nobody@example.com');

        self::assertResponseStatusCodeSame(404);
    }
}
