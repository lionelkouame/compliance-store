<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Infrastructure\Security\ApiClient;
use App\Infrastructure\Security\ApiClientRepository;

/**
 * Every endpoint but the health check requires an API key: API tests create
 * a throwaway client with the role they need and send its key as a bearer token.
 *
 * @phpstan-require-extends \ApiPlatform\Symfony\Bundle\Test\ApiTestCase
 */
trait ApiClientFixture
{
    private static int $apiClientSequence = 0;

    /**
     * @param list<string> $roles
     */
    protected static function createAuthenticatedClient(array $roles = [ApiClient::ROLE_ADMIN]): Client
    {
        return static::createClient([], [
            'headers' => ['Authorization' => 'Bearer '.self::issueApiKey($roles)],
        ]);
    }

    /**
     * @param list<string> $roles
     *
     * @return string the clear API key
     */
    protected static function issueApiKey(array $roles = [ApiClient::ROLE_ADMIN]): string
    {
        ++self::$apiClientSequence;

        [$client, $apiKey] = ApiClient::issue(
            id: \sprintf('00000000-0000-7000-8000-%012d', self::$apiClientSequence),
            name: \sprintf('test-client-%d-%s', self::$apiClientSequence, bin2hex(random_bytes(4))),
            roles: $roles,
        );

        $repository = self::getContainer()->get(ApiClientRepository::class);
        \assert($repository instanceof ApiClientRepository);
        $repository->save($client);

        return $apiKey;
    }

    protected static function clearApiClients(): void
    {
        $repository = self::getContainer()->get(ApiClientRepository::class);
        \assert($repository instanceof ApiClientRepository);
        $repository->createQueryBuilder('c')->delete()->getQuery()->execute();
    }
}
