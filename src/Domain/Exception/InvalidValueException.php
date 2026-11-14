<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * A value does not satisfy a Value Object's invariants.
 *
 * Concrete on purpose: Value Objects throw it directly instead of declaring
 * one exception class each. Its message is sent back to the API client and
 * may be logged, so it must never contain personal data.
 */
final class InvalidValueException extends InvalidDataException
{
}
