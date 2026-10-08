<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\IdentityDocument;
use App\Domain\ValueObject\IdentityDocumentId;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class IdentityDocumentApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const PASSPORT = [
        'type' => 'Passport',
        'country' => 'FRA',
        'number' => '18AB12345',
        'expiresAt' => '2035-01-31',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.IdentityDocument::class)->execute();
    }

    public function testItRegistersAnIdentityDocument(): void
    {
        $response = static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        self::assertNotEmpty($data['id']);
        self::assertSame('Passport', $data['type']);
        self::assertSame('FRA', $data['country']);
        self::assertSame('18AB12345', $data['number']);
        self::assertSame('2035-01-31', $data['expiresAt']);
    }

    public function testItRejectsMissingFields(): void
    {
        static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnInvalidCountry(): void
    {
        static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['country' => 'FR'] + self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnExpiredDocument(): void
    {
        static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['expiresAt' => '2020-01-01'] + self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsADuplicateDocument(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::PASSPORT,
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testItPersistsTheRegisteredIdentityDocument(): void
    {
        $data = static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::PASSPORT,
        ])->toArray();

        self::assertResponseStatusCodeSame(201);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $identityDocument = $entityManager->find(IdentityDocument::class, new IdentityDocumentId($data['id']));

        self::assertInstanceOf(IdentityDocument::class, $identityDocument);
        self::assertSame('Passport', $identityDocument->type()->value);
        self::assertSame('FRA', $identityDocument->country()->value);
        self::assertSame('18AB12345', $identityDocument->number()->value);
        self::assertSame('2035-01-31', $identityDocument->expiresAt()->format('Y-m-d'));
    }

    public function testItReturnsTheCreationDate(): void
    {
        $data = static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::PASSPORT,
        ])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $data['createdAt']));
    }

    public function testItRegistersANationalCard(): void
    {
        $response = static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['type' => 'NationalCard'] + self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('NationalCard', $response->toArray()['type']);
    }

    public function testItAcceptsADocumentExpiringToday(): void
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');

        static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['expiresAt' => $today] + self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'unknown type' => [['type' => 'DrivingLicence']];
        yield 'lowercase country' => [['country' => 'fra']];
        yield 'lowercase number' => [['number' => '18ab12345']];
        yield 'number with symbols' => [['number' => '18-AB-12345']];
        yield 'number too long' => [['number' => str_repeat('A', 21)]];
        yield 'blank type' => [['type' => '']];
        yield 'non-string number' => [['number' => 123456]];
        yield 'wrong date format' => [['expiresAt' => '31/01/2035']];
        yield 'impossible date' => [['expiresAt' => '2035-02-30']];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidPayloads')]
    public function testItRejectsAnInvalidPayload(array $override): void
    {
        static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $override + self::PASSPORT,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAMalformedJsonBody(): void
    {
        static::createClient()->request('POST', '/api/v1/identity-documents', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"type": "Passport",',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItAllowsTheSameNumberForAnotherCountryOrType(): void
    {
        $client = static::createClient();

        foreach ([self::PASSPORT, ['country' => 'DEU'] + self::PASSPORT, ['type' => 'NationalCard'] + self::PASSPORT] as $payload) {
            $client->request('POST', '/api/v1/identity-documents', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $payload,
            ]);

            self::assertResponseStatusCodeSame(201);
        }
    }
}
