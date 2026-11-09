<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Document\DocumentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Webmozart\Assert\Assert;

final class DocumentIdType extends StringType
{
    public const NAME = 'document_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DocumentId
    {
        return \is_string($value) ? DocumentId::make($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        Assert::isInstanceOf($value, DocumentId::class);

        return $value->value();
    }
}
