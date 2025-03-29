<?php

namespace Ambta\DoctrineEncryptBundle;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DeprecatedDoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\VersionTester;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DoctrineEncryptBundle as NewDoctrineEncryptBundle;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AmbtaDoctrineEncryptBundle extends Bundle
{
    #[Pure]
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineEncryptExtension(new VersionTester());
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        trigger_deprecation(
            'doctrineencryptbundle/doctrine-encrypt-bundle',
            '5.4.3',
            sprintf(<<<'EOF'
`%s` has been replaced by `%s`.
Starting from 6.0, only `%s` will be supported.
EOF
                ,
                self::class,
                NewDoctrineEncryptBundle::class,
                NewDoctrineEncryptBundle::class
            )
        );

        // TODO-6.0 Remove the old extension again
        $container->registerExtension(new DeprecatedDoctrineEncryptExtension(new VersionTester()));
    }
}
