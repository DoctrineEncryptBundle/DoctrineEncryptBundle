<?php

namespace DoctrineEncryptBundle\DependencyInjection;

use DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Initialization of bundle.
 *
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DoctrineEncryptExtension extends Extension
{
    public const SupportedEncryptorClasses = array(
        'Defuse' => DefuseEncryptor::class,
        'Halite' => HaliteEncryptor::class,
    );

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Create configuration object
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // If empty encryptor class, use Halite encryptor
        if (array_key_exists($config['encryptor_class'], self::SupportedEncryptorClasses)) {
            $config['encryptor_class_full'] = self::SupportedEncryptorClasses[$config['encryptor_class']];
        } else {
            $config['encryptor_class_full'] = $config['encryptor_class'];
        }

        // Set parameters
        $container->setParameter('doctrine_encrypt.encryptor_class_name', $config['encryptor_class_full']);
        $container->setParameter('doctrine_encrypt.secret_directory_path',$config['secret_directory_path']);
        $container->setParameter('doctrine_encrypt.enable_secret_generation',$config['enable_secret_generation']);

        if (isset($config['secret'])) {
            $container->setParameter('doctrine_encrypt.secret',$config['secret']);
        }

        // Load service file
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        if (!isset($config['secret'])) {
            $loader->load('services_with_secretfactory.yml');
        } else {
            $loader->load('services_with_secret.yml');
        }

        // Symfony 1-4
        // Sanity-check since this should be blocked by composer.json
        if (Kernel::MAJOR_VERSION < 5 || (Kernel::MAJOR_VERSION === 5 && Kernel::MINOR_VERSION < 4)) {
            throw new \RuntimeException('doctrineencryptbundle/doctrine-encrypt-bundle expects symfony-version >= 5.4!');
        }
    }

    /**
     * Get alias for configuration
     *
     * @return string
     */
    public function getAlias(): string
    {
        return 'doctrine_encrypt';
    }
}
