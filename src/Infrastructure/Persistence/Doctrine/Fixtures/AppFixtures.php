<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Point d'entrée des fixtures Doctrine (dev et test uniquement).
 *
 * Les fixtures dépendent de Doctrine : elles appartiennent à la couche
 * Infrastructure, sous Persistence/Doctrine (ADR 0015).
 */
final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
