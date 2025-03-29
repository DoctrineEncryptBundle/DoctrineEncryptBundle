<?php

declare(strict_types=1);

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal
 */
final class VersionTester
{
    public function isSymfony7OrHigher(): bool
    {
        return Kernel::MAJOR_VERSION > 7;
    }

    public function isPhp8OrHigher(): bool
    {
        return PHP_VERSION_ID >= 80000;
    }

    public function doctrineOrmIsVersion3(): bool
    {
        return \Composer\InstalledVersions::satisfies(
            new \Composer\Semver\VersionParser(),
            'doctrine/orm',
            '^3.0'
        );
    }
}
