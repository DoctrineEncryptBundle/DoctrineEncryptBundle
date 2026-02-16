<?php

declare(strict_types=1);

namespace Ambta\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;

final class ConfigureMappingReaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if ($container->has('annotations.reader')) {
            $loader->load('services_subscriber_with_annotations_and_attributes.yml');
        } else {
            $loader->load('service_listeners_with_attributes.yml');
        }
    }
}
