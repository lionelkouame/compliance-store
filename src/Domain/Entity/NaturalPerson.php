<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\NaturalPerson\FirstName;
use App\Domain\ValueObject\NaturalPerson\Gender;
use App\Domain\ValueObject\NaturalPerson\LastName;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

/**
 * A natural person (human being) identified by their civil identity.
 *
 * Properties are readonly: like {@see Holder}, a change produces a new
 * instance rather than mutating the current one, and the repository is
 * responsible for persisting it.
 */
final readonly class NaturalPerson
{
    private function __construct(
        private NaturalPersonId $id,
        private Gender $gender,
        private FirstName $firstName,
        private LastName $lastName,
        private BirthDate $birthDate,
        private \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        NaturalPersonId $id,
        Gender $gender,
        FirstName $firstName,
        LastName $lastName,
        BirthDate $birthDate,
    ): self {
        return new self(
            id: $id,
            gender: $gender,
            firstName: $firstName,
            lastName: $lastName,
            birthDate: $birthDate,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function id(): NaturalPersonId
    {
        return $this->id;
    }

    public function gender(): Gender
    {
        return $this->gender;
    }

    public function firstName(): FirstName
    {
        return $this->firstName;
    }

    public function lastName(): LastName
    {
        return $this->lastName;
    }

    public function birthDate(): BirthDate
    {
        return $this->birthDate;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
