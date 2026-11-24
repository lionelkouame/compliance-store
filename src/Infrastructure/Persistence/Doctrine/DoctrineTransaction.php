<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Port\Persistence\TransactionInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les repositories font déjà leur propre flush() : à l'intérieur de
 * wrapInTransaction(), ces flush() rejoignent la transaction englobante
 * (Doctrine imbrique les transactions par points de sauvegarde), si bien que
 * rien n'est validé avant la fin de l'opération.
 *
 * En cas d'échec, Doctrine ferme l'EntityManager en plus d'annuler la
 * transaction : sans conséquence dans une requête HTTP, qui s'arrête là, mais
 * un traitement long (worker, commande) devra en tenir compte.
 */
final readonly class DoctrineTransaction implements TransactionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function run(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(static fn (): mixed => $operation());
    }
}
