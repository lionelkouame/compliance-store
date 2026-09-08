<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\StorageSpace;
use Doctrine\ORM\EntityManagerInterface;

final class StorageSpaceApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.StorageSpace::class)->execute();
    }

    public function testItCreatesAStorageSpace(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        self::assertSame('EU-WEST-1', $data['code']);
        self::assertSame('EU West Primary', $data['name']);
        self::assertSame('active', $data['status']);
        self::assertNotEmpty($data['id']);
    }

    public function testItRejectsMissingFields(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testItRejectsADuplicateCode(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'Another name'],
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testItGetsAndListsStorageSpaces(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ])->toArray();

        $client->request('GET', '/api/v1/storage-spaces/'.$created['id']);
        self::assertResponseStatusCodeSame(200);

        $list = $client->request('GET', '/api/v1/storage-spaces')->toArray();
        self::assertSame(1, $list['totalItems']);
    }

    public function testItReturns404ForAnUnknownStorageSpace(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/storage-spaces/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItChangesAStorageSpaceStatus(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ])->toArray();

        $response = $client->request('PATCH', '/api/v1/storage-spaces/'.$created['id'], [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'archived'],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame('archived', $response->toArray()['status']);
    }

    public function testItRejectsAnInvalidStatusTransition(): void
    {
        $client = static::createClient();

        $created = $client->request('POST', '/api/v1/storage-spaces', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['code' => 'EU-WEST-1', 'name' => 'EU West Primary'],
        ])->toArray();

        $client->request('PATCH', '/api/v1/storage-spaces/'.$created['id'], [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['status' => 'bogus'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
