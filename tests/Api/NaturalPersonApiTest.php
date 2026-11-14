<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\NaturalPerson;
use Doctrine\ORM\EntityManagerInterface;

final class NaturalPersonApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const NATURAL_PERSON = [
        'gender' => 'male',
        'firstName' => 'Lionel',
        'lastName' => 'Kouame',
        'birthday' => '1990-01-01',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.NaturalPerson::class)->execute();
    }

    public function testItCreatesANaturalPerson(): void
    {
        $data = static::createClient()->request('POST', '/api/v1/natural-persons', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::NATURAL_PERSON,
        ])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertNotEmpty($data['identifier']);
    }

    public function testItRejectsMissingFields(): void
    {
        static::createClient()->request('POST', '/api/v1/natural-persons', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnUnknownGender(): void
    {
        static::createClient()->request('POST', '/api/v1/natural-persons', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['gender' => 'bogus'] + self::NATURAL_PERSON,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItGetsANaturalPerson(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/natural-persons', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::NATURAL_PERSON,
        ])->toArray();

        $data = $client->request('GET', '/api/v1/natural-persons/'.$created['identifier'])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created['identifier'], $data['identifier']);
        self::assertSame('male', $data['gender']);
        self::assertSame('Lionel', $data['firstName']);
        self::assertSame('Kouame', $data['lastName']);
        self::assertSame('1990-01-01', $data['birthDate']);
        self::assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $data['createdAt']));
    }

    public function testItReturns404ForAnUnknownNaturalPerson(): void
    {
        static::createClient()->request('GET', '/api/v1/natural-persons/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
