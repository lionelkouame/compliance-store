<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Holder\HolderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Webmozart\Assert\Assert;

final class HolderIdType extends StringType
{
    public const NAME = 'holder_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HolderId
    {
        return \is_string($value) ? HolderId::make($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        Assert::isInstanceOf($value, HolderId::class);

        return $value->value();
    }
}
