<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Root of every business exception. Abstract: a business exception must
 * extend one of the categories mapped to an HTTP status (NotFoundException,
 * AlreadyExistException, InvalidDataException), otherwise it would surface
 * as a 500 (ADR 0013). Enforced by BusinessExceptionsAreCategorisedTest.
 */
abstract class DomainException extends \DomainException
{
}
