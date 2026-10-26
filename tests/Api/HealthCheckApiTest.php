<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class HealthCheckApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testItReportsTheRealEnvironmentAndVersion(): void
    {
        $response = static::createClient()->request('GET', '/api/v1/health', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseIsSuccessful();

        $data = $response->toArray();
        self::assertSame('OK', $data['status']);
        self::assertSame('test', $data['environment']);
        self::assertSame('1.0.0', $data['version']);
        self::assertNotFalse(\DateTimeImmutable::createFromFormat(\DATE_ATOM, $data['timestamp']));
    }
}
