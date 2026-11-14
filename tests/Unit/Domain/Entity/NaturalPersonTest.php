<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\NaturalPerson;
use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\NaturalPerson\FirstName;
use App\Domain\ValueObject\NaturalPerson\Gender;
use App\Domain\ValueObject\NaturalPerson\LastName;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;
use PHPUnit\Framework\TestCase;

final class NaturalPersonTest extends TestCase
{
    public function testCreateBuildsANaturalPersonStampedInUtc(): void
    {
        $naturalPerson = $this->create();

        self::assertSame('Lionel', (string) $naturalPerson->firstName());
        self::assertSame('Kouame', (string) $naturalPerson->lastName());
        self::assertSame(Gender::Male, $naturalPerson->gender());
        self::assertSame('1990-05-12', (string) $naturalPerson->birthDate());
        self::assertSame('UTC', $naturalPerson->createdAt()->getTimezone()->getName());
    }

    public function testGenderDefaultsToUnspecifiedWhenNotProvided(): void
    {
        self::assertSame(Gender::Unspecified, Gender::fromNullable(null));
        self::assertSame(Gender::Unspecified, Gender::fromNullable(''));
    }

    public function testGenderIsCaseInsensitive(): void
    {
        self::assertSame(Gender::Female, Gender::fromNullable('FEMALE'));
    }

    public function testGenderRejectsUnknownValues(): void
    {
        $this->expectException(InvalidValueException::class);

        Gender::fromNullable('alien');
    }

    public function testFirstNameCannotBeEmpty(): void
    {
        $this->expectException(InvalidValueException::class);

        new FirstName('   ');
    }

    public function testLastNameCannotBeEmpty(): void
    {
        $this->expectException(InvalidValueException::class);

        new LastName('');
    }

    public function testBirthDateMustBeInThePast(): void
    {
        $this->expectException(InvalidValueException::class);

        new BirthDate(new \DateTimeImmutable('tomorrow', new \DateTimeZone('UTC')));
    }

    private function create(): NaturalPerson
    {
        return NaturalPerson::create(
            id: NaturalPersonId::make('770e8400-e29b-41d4-a716-446655440222'),
            gender: Gender::Male,
            firstName: new FirstName('Lionel'),
            lastName: new LastName('Kouame'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-05-12', new \DateTimeZone('UTC'))),
        );
    }
}
