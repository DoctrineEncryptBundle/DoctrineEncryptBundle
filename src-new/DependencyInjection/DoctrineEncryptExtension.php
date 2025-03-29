<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class DoctrineEncryptExtension extends \Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension
{
    public const SupportedEncryptorClasses = [
        'Defuse' => DefuseEncryptor::class,
        'Halite' => HaliteEncryptor::class,
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration($this->getAlias()), $configs);

        (new ServiceConfigurator($this->versionTester, true))->configure($config, $container);
    }

    /**
     * Get alias for configuration.
     */
    public function getAlias(): string
    {
        return 'doctrine_encrypt';
    }
}
