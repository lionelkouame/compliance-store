<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderId;

interface HolderRepositoryInterface
{
    public function findById(HolderId $id): ?Holder;

    public function findByEmail(Email $email): ?Holder;

    /**
     * @throws HolderAlreadyExistsException when the email is already taken, including by a concurrent insert
     */
    public function add(Holder $holder): void;

    /**
     * Enregistre la nouvelle version d'un titulaire existant (le titulaire est
     * immuable : chaque transition produit une nouvelle instance).
     */
    public function update(Holder $holder): void;
}
