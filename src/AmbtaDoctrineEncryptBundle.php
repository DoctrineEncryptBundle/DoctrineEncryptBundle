<?php

namespace Ambta\DoctrineEncryptBundle;

use Ambta\DoctrineEncryptBundle\DependencyInjection\ConfigureMappingReaderPass;
use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AmbtaDoctrineEncryptBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new ConfigureMappingReaderPass(),
            priority: 20, // Higher than Doctrine's default priority to make sure orm-subscriber/listener are properly registered
        );
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineEncryptExtension();
    }
}
