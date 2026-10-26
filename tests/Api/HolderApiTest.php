<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Holder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class HolderApiTest extends ApiTestCase
{
    use ApiClientFixture;

    protected static ?bool $alwaysBootKernel = true;

    private const ADA = [
        'holderName' => 'Ada Lovelace',
        'birthDate' => '1990-01-01',
        'email' => 'ada.lovelace@example.com',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        self::clearApiClients();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
    }

    public function testItRegistersAHolder(): void
    {
        $response = self::createAuthenticatedClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::ADA,
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        self::assertNotEmpty($data['id']);
        self::assertSame('Ada Lovelace', $data['holderName']);
        self::assertSame('1990-01-01', $data['birthDate']);
        self::assertSame('ada.lovelace@example.com', $data['email']);
        self::assertSame('active', $data['status']);
        self::assertNull($data['consentCollectedAt']);
        self::assertNull($data['erasureRequestedAt']);
        self::assertNull($data['anonymizedAt']);
    }

    public function testItGetsARegisteredHolder(): void
    {
        $client = self::createAuthenticatedClient();

        $created = $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::ADA,
        ])->toArray();

        $response = $client->request('GET', '/api/v1/holders/'.$created['id']);

        self::assertResponseStatusCodeSame(200);

        $data = $response->toArray();
        self::assertSame($created['id'], $data['id']);
        self::assertSame('Ada Lovelace', $data['holderName']);
        self::assertSame('1990-01-01', $data['birthDate']);
        self::assertSame('active', $data['status']);
        self::assertSame($created['createdAt'], $data['createdAt']);
    }

    public function testItReturns404ForAnUnknownHolder(): void
    {
        self::createAuthenticatedClient()->request('GET', '/api/v1/holders/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItReturns404ForAnInvalidHolderId(): void
    {
        self::createAuthenticatedClient()->request('GET', '/api/v1/holders/not-a-uuid');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItRejectsMissingFields(): void
    {
        self::createAuthenticatedClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsAnEmailAlreadyUsedByAnotherHolder(): void
    {
        $client = self::createAuthenticatedClient();
        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => self::ADA,
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Another Ada'] + self::ADA,
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testItRejectsAnInvalidEmail(): void
    {
        self::createAuthenticatedClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['email' => 'not-an-email'] + self::ADA,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBirthDates(): iterable
    {
        yield 'not a date' => ['string'];
        yield 'wrong format' => ['01/01/1990'];
        yield 'impossible day' => ['1990-02-30'];
        yield 'in the future' => ['2999-01-01'];
    }

    #[DataProvider('invalidBirthDates')]
    public function testItRejectsAnInvalidBirthDate(string $birthDate): void
    {
        self::createAuthenticatedClient()->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Ada Lovelace', 'birthDate' => $birthDate, 'email' => 'ada.lovelace@example.com'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
