<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Holder;
use App\Domain\Entity\StorageSpace;
use App\Infrastructure\Security\ApiClient;
use App\Infrastructure\Security\ApiClientRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AuthenticationApiTest extends ApiTestCase
{
    use ApiClientFixture;

    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
        $entityManager->createQuery('DELETE FROM '.StorageSpace::class)->execute();
        self::clearApiClients();
    }

    public function testTheHealthCheckNeedsNoApiKey(): void
    {
        static::createClient()->request('GET', '/api/v1/health');

        self::assertResponseIsSuccessful();
    }

    public function testTheOpenApiDocumentationNeedsNoApiKey(): void
    {
        static::createClient()->request('GET', '/api/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        self::assertResponseIsSuccessful();
    }

    public function testARequestWithoutApiKeyIsRejectedWith401(): void
    {
        static::createClient()->request('GET', '/api/v1/storage-spaces');

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('www-authenticate', 'Bearer realm="Compliance Store API"');
    }

    public function testAnUnknownApiKeyIsRejectedWith401(): void
    {
        static::createClient()->request('GET', '/api/v1/storage-spaces', [
            'headers' => ['Authorization' => 'Bearer cs_unknown'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testARevokedApiKeyIsRejectedWith401(): void
    {
        $apiKey = self::issueApiKey([ApiClient::ROLE_ADMIN]);
        $repository = self::getContainer()->get(ApiClientRepository::class);
        \assert($repository instanceof ApiClientRepository);
        $client = $repository->findActiveByApiKey($apiKey);
        self::assertNotNull($client);

        $client->revoke();
        $repository->save($client);

        static::createClient()->request('GET', '/api/v1/storage-spaces', [
            'headers' => ['Authorization' => 'Bearer '.$apiKey],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAReaderCanReadButNotWrite(): void
    {
        $client = self::createAuthenticatedClient([ApiClient::ROLE_READER]);

        $client->request('GET', '/api/v1/storage-spaces');
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Ada Lovelace', 'birthDate' => '1990-01-01', 'email' => bin2hex(random_bytes(6)).'@example.com'],
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnOperatorRegistersHoldersButDoesNotManageStorageSpaces(): void
    {
        $client = self::createAuthenticatedClient([ApiClient::ROLE_OPERATOR]);

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Ada Lovelace', 'birthDate' => '1990-01-01', 'email' => bin2hex(random_bytes(6)).'@example.com'],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnAdminInheritsEveryRole(): void
    {
        $client = self::createAuthenticatedClient([ApiClient::ROLE_ADMIN]);

        $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/holders', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['holderName' => 'Ada Lovelace', 'birthDate' => '1990-01-01', 'email' => bin2hex(random_bytes(6)).'@example.com'],
        ]);
        self::assertResponseStatusCodeSame(201);
    }
}
