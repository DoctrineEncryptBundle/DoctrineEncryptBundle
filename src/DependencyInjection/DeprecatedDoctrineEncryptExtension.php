<?php

namespace Ambta\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Initialization of bundle.
 *
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DeprecatedDoctrineEncryptExtension extends DoctrineEncryptExtension
{
    public function getAlias(): string
    {
        return 'ambta_doctrine_encrypt';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!empty($configs)) {
            trigger_deprecation(
                'doctrineencryptbundle/doctrine-encrypt-bundle',
                '5.4.3',
                <<<'EOF'
Using `ambta_doctrine_encrypt` as the configuration-key is deprecated and you should replace this with `doctrine_encrypt`.
Starting from 6.0, only `doctrine_encrypt` will be supported.
EOF
            );

            parent::load($configs, $container);
        }
    }
}
