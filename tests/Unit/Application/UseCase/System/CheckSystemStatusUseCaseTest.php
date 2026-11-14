<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\System;

use App\Application\UseCase\System\CheckSystemStatus\CheckSystemStatusRequest;
use App\Application\UseCase\System\CheckSystemStatus\CheckSystemStatusUseCase;
use PHPUnit\Framework\TestCase;

final class CheckSystemStatusUseCaseTest extends TestCase
{
    public function testExecuteReportsAHealthySystem(): void
    {
        $readModel = (new CheckSystemStatusUseCase(environment: 'test', version: '2.0.0'))
            ->execute(new CheckSystemStatusRequest())
            ->readModel;

        self::assertSame(CheckSystemStatusUseCase::STATUS_OK, $readModel->status);
        self::assertSame('test', $readModel->environment);
        self::assertSame('2.0.0', $readModel->version);
        self::assertSame('UTC', $readModel->timestamp->getTimezone()->getName());
    }
}
