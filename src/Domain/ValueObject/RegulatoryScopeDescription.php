<?php

namespace App\Domain\ValueObject;

final readonly class RegulatoryScopeDescription
{
    public function __construct(
        public string $value = '',
    ) {}
}
