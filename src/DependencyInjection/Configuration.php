<?php

namespace Ambta\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for security bundle. Full tree you can see in Resources/docs.
 *
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @deprecated Use \DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\Configuration instead.
 *             This class will be removed in 6.0.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $rootName;

    public function __construct($rootName)
    {
        $this->rootName = $rootName;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        // Create tree builder
        $treeBuilder = new TreeBuilder($this->rootName);
        if (\method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root($this->rootName);
        }

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
                    ->defaultTrue()
                ->end()
                ->scalarNode('secret')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('wrap_exceptions')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
