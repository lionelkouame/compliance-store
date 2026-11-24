<?php

declare(strict_types=1);

namespace App\Domain\Port\Persistence;

/**
 * Exécute plusieurs écritures en base comme une seule unité : toutes sont
 * validées, ou aucune.
 *
 * À réserver aux use cases qui écrivent plusieurs fois : une écriture isolée
 * (un flush, un UPDATE) est déjà atomique. Ne jamais y placer d'appel réseau
 * (stockage objet, API externe) : la transaction resterait ouverte pendant
 * l'appel, et ces systèmes n'y participent de toute façon pas.
 */
interface TransactionInterface
{
    /**
     * Exécute l'opération dans une transaction, validée si elle se termine
     * normalement et annulée si elle lève une exception (relancée telle quelle).
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed;
}
