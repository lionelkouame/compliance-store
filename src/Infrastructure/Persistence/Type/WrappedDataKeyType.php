<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\WrappedDataKey;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class WrappedDataKeyType extends Type
{
    public const NAME = 'wrapped_data_key';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBinaryTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?WrappedDataKey
    {
        if (null === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return new WrappedDataKey((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof WrappedDataKey ? $value->encryptedDataKey : (string) $value;
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::BINARY;
    }
}
