<?php

declare(strict_types=1);

namespace Ambta\DoctrineEncryptBundle\Tests\Unit;

use Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle;
use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class AmbtaDoctrineEncryptBundleTest extends TestCase
{
    use ExpectDeprecationTrait;

    private function createContainer(): ContainerBuilder
    {
        return new ContainerBuilder(
            new ParameterBag(['kernel.debug' => false])
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DoctrineEncryptExtension::wrapExceptions(false);
    }

    /**
     * @runInSeparateProcess
     *
     * @group legacy
     */
    public function testContainerIsAbleToConfigFromOldNamespace(): void
    {
        $container = $this->createContainer();

        $bundle = new AmbtaDoctrineEncryptBundle();

        $container->registerExtension($bundle->getContainerExtension());
        $bundle->build($container);

        $yamlLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../_data'));
        $yamlLoader->load('ambta_doctrine_encrypt.yaml');

        $container->addCompilerPass(new MergeExtensionConfigurationPass());

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.3: Using `ambta_doctrine_encrypt` as the configuration-key is deprecated and you should replace this with `doctrine_encrypt`.
Starting from 6.0, only `doctrine_encrypt` will be supported.');

        $container->compile();

        $this->assertTrue($container->hasParameter('ambta_doctrine_encrypt.secret'));
        $this->assertEquals('ambta_doctrine_encrypt.yaml', $container->getParameter('ambta_doctrine_encrypt.secret'));
    }

    /**
     * @runInSeparateProcess
     *
     * @group legacy
     */
    public function testContainerIsAbleToConfigFromNewNamespace(): void
    {
        $container = $this->createContainer();

        $bundle = new AmbtaDoctrineEncryptBundle();

        $container->registerExtension($bundle->getContainerExtension());
        $bundle->build($container);

        $yamlLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../_data'));
        $yamlLoader->load('doctrine_encrypt.yaml');

        $container->addCompilerPass(new MergeExtensionConfigurationPass());

        $this->expectDeprecation(<<<'EOF'
Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.3: `Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle` has been replaced by `DoctrineEncryptBundle\DoctrineEncryptBundle\DoctrineEncryptBundle`.
Starting from 6.0, only `DoctrineEncryptBundle\DoctrineEncryptBundle\DoctrineEncryptBundle` will be supported.
EOF
        );
        $container->compile();

        $this->assertTrue($container->hasParameter('ambta_doctrine_encrypt.secret'));
        $this->assertEquals('doctrine_encrypt_bundle.yaml', $container->getParameter('ambta_doctrine_encrypt.secret'));
    }
}
