<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\ApiClient;
use PHPUnit\Framework\TestCase;

final class ApiClientTest extends TestCase
{
    public function testIssueReturnsAPrefixedKeyAndStoresOnlyItsHash(): void
    {
        [$client, $apiKey] = ApiClient::issue('00000000-0000-7000-8000-000000000001', ' back-office ', [ApiClient::ROLE_OPERATOR]);

        self::assertStringStartsWith('cs_', $apiKey);
        self::assertGreaterThanOrEqual(43 + 3, \strlen($apiKey));
        self::assertSame('back-office', $client->name());
        self::assertSame('back-office', $client->getUserIdentifier());
        self::assertSame([ApiClient::ROLE_OPERATOR], $client->getRoles());
        self::assertFalse($client->isRevoked());
        self::assertStringNotContainsString($apiKey, serialize($client));
        self::assertStringNotContainsString($apiKey, print_r($client, true));
    }

    public function testTwoIssuedKeysNeverCollide(): void
    {
        [, $first] = ApiClient::issue('00000000-0000-7000-8000-000000000001', 'a', [ApiClient::ROLE_READER]);
        [, $second] = ApiClient::issue('00000000-0000-7000-8000-000000000002', 'b', [ApiClient::ROLE_READER]);

        self::assertNotSame($first, $second);
    }

    public function testRevokeIsIdempotent(): void
    {
        [$client] = ApiClient::issue('00000000-0000-7000-8000-000000000001', 'a', [ApiClient::ROLE_READER]);

        $client->revoke();
        $revokedAt = $client->revokedAt();
        $client->revoke();

        self::assertTrue($client->isRevoked());
        self::assertSame($revokedAt, $client->revokedAt());
    }

    public function testIssueRejectsAnUnknownRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ApiClient::issue('00000000-0000-7000-8000-000000000001', 'a', ['ROLE_GOD']);
    }

    public function testIssueRejectsAnEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ApiClient::issue('00000000-0000-7000-8000-000000000001', '   ', [ApiClient::ROLE_READER]);
    }

    public function testIssueRejectsNoRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ApiClient::issue('00000000-0000-7000-8000-000000000001', 'a', []);
    }
}
