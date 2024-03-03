<?php

namespace DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for security bundle. Full tree you can see in Resources/docs
 *
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        // Create tree builder
        $treeBuilder = new TreeBuilder('doctrine_encrypt');
        $rootNode = $treeBuilder->getRootNode();

        // Grammar of config tree
        $rootNode
            ->beforeNormalization()
                ->always(function ($v) {
                    if (isset($v['secret']) && isset($v['secret_directory_path'])) {
                        throw new \InvalidArgumentException('The "secret" and "secret_directory_path" cannot be used along together.');
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('encryptor_class')
                    ->defaultValue('Halite')
                ->end()
                ->scalarNode('secret_directory_path')
                    ->defaultValue('%kernel.project_dir%')
                ->end()
                ->booleanNode('enable_secret_generation')
                    ->defaultValue(true)
                ->end()
                ->scalarNode('secret')
                    ->defaultValue(null)
                ->end()
            ->end();

        return $treeBuilder;
    }

}
