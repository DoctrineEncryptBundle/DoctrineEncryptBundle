<?php

declare(strict_types=1);

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Unit;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DoctrineEncryptBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DoctrineEncryptBundleTest extends TestCase
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
     * @group legacy
     */
    public function testContainerIsAbleToConfigFromNewNamespace(): void
    {
        $container = $this->createContainer();

        $bundle = new DoctrineEncryptBundle();

        $container->registerExtension($bundle->getContainerExtension());
        $bundle->build($container);

        $yamlLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../_data'));
        $yamlLoader->load('doctrine_encrypt.yaml');

        $container->addCompilerPass(new MergeExtensionConfigurationPass());

        // TODO-6.0 Remove deprecation-test
        $this->expectDeprecation('');
        $container->compile();

        $this->assertTrue($container->hasParameter('doctrine_encrypt.secret'));
        $this->assertEquals('doctrine_encrypt_bundle.yaml', $container->getParameter('doctrine_encrypt.secret'));
    }
}
