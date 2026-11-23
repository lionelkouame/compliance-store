<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use App\Domain\Exception\AlreadyExistException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\InvalidDataException;
use App\Domain\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Only the categories below are translated into an HTTP status
 * (config/packages/api_platform.yaml). A concrete business exception that
 * extends none of them would surface as a 500 (ADR 0013).
 *
 * PolicyViolationException is deliberately absent: its status is still an
 * open point of ADR 0013, so its first concrete subclass fails this test until
 * the category is mapped and added here.
 */
final class BusinessExceptionsAreCategorisedTest extends TestCase
{
    /** @var list<class-string<DomainException>> */
    private const array MAPPED_CATEGORIES = [
        NotFoundException::class,
        AlreadyExistException::class,
        InvalidDataException::class,
    ];

    public function testEveryConcreteBusinessExceptionExtendsAMappedCategory(): void
    {
        $directory = __DIR__.'/../../../src/Domain/Exception';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        $checked = 0;
        $offenders = [];

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }

            $relative = substr($file->getPathname(), \strlen($directory) + 1, -4);
            $class = 'App\\Domain\\Exception\\'.str_replace('/', '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            ++$checked;
            $isMapped = array_filter(self::MAPPED_CATEGORIES, static fn (string $category): bool => is_a($class, $category, true));
            if ([] === $isMapped) {
                $offenders[] = $class;
            }
        }

        self::assertGreaterThan(0, $checked, 'No concrete business exception found: is the directory right?');
        self::assertSame([], $offenders, 'Extend NotFoundException, AlreadyExistException or InvalidDataException (ADR 0013).');
    }

    public function testTheRootBusinessExceptionCannotBeThrownDirectly(): void
    {
        self::assertTrue((new \ReflectionClass(DomainException::class))->isAbstract());
    }
}
