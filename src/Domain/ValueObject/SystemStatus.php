<?php

namespace App\Domain\ValueObject;

final readonly class SystemStatus
{
    public function __construct(
        public string $status,
        public string $version,
        public string $environment,
        public \DateTimeImmutable $timestamp,
    ) {}
}
