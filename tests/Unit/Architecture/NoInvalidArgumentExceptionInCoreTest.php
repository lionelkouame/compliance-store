<?php

declare(strict_types=1);

namespace App\Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Domain and Application must throw categorised business exceptions
 * (src/Domain/Exception), never a generic \InvalidArgumentException:
 * only the categories are translated into HTTP statuses (api_platform.yaml),
 * so a generic exception would surface as a 500.
 */
final class NoInvalidArgumentExceptionInCoreTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function coreLayers(): iterable
    {
        yield 'Domain' => [__DIR__.'/../../../src/Domain'];
        yield 'Application' => [__DIR__.'/../../../src/Application'];
    }

    #[DataProvider('coreLayers')]
    public function testLayerDoesNotUseInvalidArgumentException(string $directory): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }

            if (str_contains((string) file_get_contents($file->getPathname()), 'InvalidArgumentException')) {
                $offenders[] = $file->getPathname();
            }
        }

        self::assertSame([], $offenders, 'Throw App\Domain\Exception\InvalidValueException (or another business category) instead.');
    }
}
