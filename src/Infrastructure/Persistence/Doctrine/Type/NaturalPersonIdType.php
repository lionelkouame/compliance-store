<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Webmozart\Assert\Assert;

final class NaturalPersonIdType extends StringType
{
    public const NAME = 'natural_person_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?NaturalPersonId
    {
        return \is_string($value) ? NaturalPersonId::make($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        Assert::isInstanceOf($value, NaturalPersonId::class);

        return $value->value();
    }
}
