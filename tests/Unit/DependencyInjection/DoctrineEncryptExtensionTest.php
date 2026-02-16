<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Unit\DependencyInjection;

use Ambta\DoctrineEncryptBundle\DependencyInjection\ConfigureMappingReaderPass;
use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeReader;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\ExpressionLanguage\Expression;

class DoctrineEncryptExtensionTest extends TestCase
{
    use ExpectDeprecationTrait;

    private DoctrineEncryptExtension $extension;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->extension          = new DoctrineEncryptExtension();
        $this->temporaryDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.sha1(mt_rand());
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        unlink($this->temporaryDirectory);
    }

    public function testConfigLoadHaliteByDefault(): void
    {
        $container = $this->createContainer();
        $this->extension->load([[]], $container);

        static::assertSame(HaliteEncryptor::class, $container->getParameter('ambta_doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigLoadHalite(): void
    {
        $container = $this->createContainer();
        $config    = [
            'encryptor_class' => 'Halite',
        ];
        $this->extension->load([$config], $container);

        static::assertSame(HaliteEncryptor::class, $container->getParameter('ambta_doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigLoadDefuse(): void
    {
        $container = $this->createContainer();

        $config = [
            'encryptor_class' => 'Defuse',
        ];
        $this->extension->load([$config], $container);

        static::assertSame(DefuseEncryptor::class, $container->getParameter('ambta_doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigLoadCustomEncryptor(): void
    {
        $container = $this->createContainer();
        $config    = [
            'encryptor_class' => self::class,
        ];
        $this->extension->load([$config], $container);

        static::assertSame(self::class, $container->getParameter('ambta_doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigImpossibleToUseSecretAndSecretDirectoryPath(): void
    {
        $container = $this->createContainer();
        $config    = [
            'secret'                => 'my-secret',
            'secret_directory_path' => 'var',
        ];

        $this->expectException(\InvalidArgumentException::class);

        $this->extension->load([$config], $container);
    }

    public function testConfigUseSecret(): void
    {
        $container = $this->createContainer();
        $config    = [
            'secret' => 'my-secret',
        ];
        $this->extension->load([$config], $container);

        static::assertIsString($container->getParameter('ambta_doctrine_encrypt.secret'));
        $this->assertStringNotContainsString('Halite', $container->getParameter('ambta_doctrine_encrypt.secret'));
        $this->assertStringNotContainsString('.key', $container->getParameter('ambta_doctrine_encrypt.secret'));
        static::assertEquals('my-secret', $container->getParameter('ambta_doctrine_encrypt.secret'));
    }

    public function testHaliteSecretIsCreatedWhenSecretFileDoesNotExistAndSecretCreationIsEnabled(): void
    {
        $container = $this->createContainer();
        $config    = [
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => true,
        ];
        $this->extension->load([$config], $container);

        $secretArgument = $container->getDefinition('ambta_doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        static::assertIsString($actualSecret);
        $actualSecretOnDisk = file_get_contents($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.Halite.key');
        static::assertEquals($actualSecret, $actualSecretOnDisk);

        try {
            KeyFactory::importEncryptionKey(new HiddenString($actualSecret));
        } catch (\Throwable $e) {
            $this->fail('Generated key is not valid');
        }
    }

    public function testDefuseSecretIsCreatedWhenSecretFileDoesNotExistAndSecretCreationIsEnabled(): void
    {
        $container = $this->createContainer();
        $config    = [
            'encryptor_class'          => 'Defuse',
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => true,
        ];
        $this->extension->load([$config], $container);

        $secretArgument = $container->getDefinition('ambta_doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        static::assertIsString($actualSecret);
        $actualSecretOnDisk = file_get_contents($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.Defuse.key');
        static::assertEquals($actualSecret, $actualSecretOnDisk);

        if (strlen(hex2bin($actualSecret)) !== 255) {
            $this->fail('Generated key is not valid');
        }
    }

    public function testSecretIsNotCreatedWhenSecretFileDoesNotExistAndSecretCreationIsNotEnabled(): void
    {
        $container = $this->createContainer();
        $config    = [
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => false,
        ];
        $this->extension->load([$config], $container);

        $this->expectException(\RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/DoctrineEncryptBundle: Unable to create secret.*/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/DoctrineEncryptBundle: Unable to create secret.*/');
        } else {
            // Unable to see if the exception matches the actual message.
            $this->markAsRisky();
        }

        $secretArgument = $container->getDefinition('ambta_doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $container->resolveServices($secretArgument);
        }
    }

    public function testSecretsAreReadFromFile(): void
    {
        // Create secret
        $expectedSecret = 'my-secret';
        file_put_contents($this->temporaryDirectory.'/.Halite.key', $expectedSecret);

        $container = $this->createContainer();
        $config    = [
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => false,
        ];
        $this->extension->load([$config], $container);

        $secretArgument = $container->getDefinition('ambta_doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        static::assertIsString($actualSecret);
        static::assertEquals($expectedSecret, $actualSecret);
    }

    public function testLoadConfigWithFrameworkAnnotationsDisabled(): void
    {
        $container = $this->createContainerWithFrameworkBundle(annotationsEnabled: false);
        $this->extension->load([], $container);

        // Register services for annotation/attribute reading
        (new ConfigureMappingReaderPass())->process($container);

        static::assertTrue($container->hasDefinition('ambta_doctrine_attribute_reader'));
        static::assertSame(AttributeReader::class, $container->getDefinition('ambta_doctrine_attribute_reader')->getClass());

        static::assertTrue($container->hasAlias('ambta_doctrine_annotation_reader'));
        static::assertSame('ambta_doctrine_attribute_reader', (string) $container->getAlias('ambta_doctrine_annotation_reader'));
    }

    public function testLoadConfigWithFrameworkAnnotationsEnabled(): void
    {
        $container = $this->createContainerWithFrameworkBundle(annotationsEnabled: true);
        $this->extension->load([], $container);

        // Register services for annotation/attribute reading
        (new ConfigureMappingReaderPass())->process($container);

        static::assertTrue($container->hasDefinition('ambta_doctrine_attribute_reader'));
        static::assertSame(AttributeReader::class, $container->getDefinition('ambta_doctrine_attribute_reader')->getClass());

        static::assertTrue($container->hasDefinition('ambta_doctrine_annotation_reader'));
        static::assertSame(AttributeAnnotationReader::class, $container->getDefinition('ambta_doctrine_annotation_reader')->getClass());
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag(['kernel.debug' => false])
        );

        return $container;
    }

    private function createContainerWithFrameworkBundle(bool $annotationsEnabled): ContainerBuilder
    {
        $container = new ContainerBuilder();

        if ($annotationsEnabled) {
            $container->setDefinition('annotations.reader', new Definition());
        }

        return $container;
    }
}
