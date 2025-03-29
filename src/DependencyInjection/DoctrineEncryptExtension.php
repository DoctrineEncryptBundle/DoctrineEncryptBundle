<?php

namespace Ambta\DoctrineEncryptBundle\DependencyInjection;

use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\ServiceConfigurator;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\VersionTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Initialization of bundle.
 *
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DoctrineEncryptExtension extends Extension
{
    /**
     * @var VersionTester
     */
    protected $versionTester;

    public function __construct(
        ?VersionTester $versionTester = null
    ) {
        $this->versionTester = $versionTester ?? new VersionTester();
    }

    /**
     * Flag to test if we should wrap exceptions by our own exceptions.
     *
     * @internal
     */
    private static $wrapExceptions = false;

    /**
     * @internal
     */
    public static function wrapExceptions(?bool $wrapExceptions = null): bool
    {
        if ($wrapExceptions !== null) {
            self::$wrapExceptions = $wrapExceptions;
        }

        return self::$wrapExceptions;
    }

    public const SupportedEncryptorClasses = [
        'Defuse' => DefuseEncryptor::class,
        'Halite' => HaliteEncryptor::class,
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration($this->getAlias()), $configs);

        (new ServiceConfigurator($this->versionTester, false))->configure($config, $container);
    }

    /**
     * Get alias for configuration.
     */
    public function getAlias(): string
    {
        return 'doctrine_encrypt';
    }
}
