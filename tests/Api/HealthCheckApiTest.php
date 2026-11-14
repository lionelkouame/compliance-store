<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class HealthCheckApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testItReportsTheRealEnvironmentAndVersion(): void
    {
        $data = static::createClient()->request('GET', '/api/v1/health', [
            'headers' => ['Accept' => 'application/json'],
        ])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('OK', $data['status']);
        self::assertSame('test', $data['environment']);
        self::assertSame(self::getContainer()->getParameter('app.version'), $data['version']);
    }
}
