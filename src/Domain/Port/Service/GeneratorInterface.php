<?php

namespace App\Domain\Port\Service;

interface GeneratorInterface
{
    public function generate(): string;
}
