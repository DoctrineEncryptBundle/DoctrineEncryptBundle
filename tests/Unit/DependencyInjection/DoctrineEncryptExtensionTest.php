<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Unit\DependencyInjection;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\ExpressionLanguage\Expression;

class DoctrineEncryptExtensionTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @var DoctrineEncryptExtension
     */
    private $extension;

    private $temporaryDirectory;

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

    /**
     * @group legacy
     */
    public function testWrapExceptionsTriggersDeprecationWarningWhenNotDefiningTheOption(): void
    {
        $container = $this->createContainer();
        $config    = [];

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.2: Starting from 6.0, all exceptions thrown by this library will be wrapped by \Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting \'ambta_doctrine_encrypt.wrap_exceptions\' to TRUE.');
        $this->extension->load([$config], $container);
        $this->assertFalse(DoctrineEncryptExtension::$wrapExceptions);
    }

    /**
     * @group legacy
     */
    public function testWrapExceptionsTriggersDeprecationWarningWhenDisabled(): void
    {
        $container = $this->createContainer();
        $config    = ['wrap_exceptions' => false];

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.2: Starting from 6.0, all exceptions thrown by this library will be wrapped by \Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting \'ambta_doctrine_encrypt.wrap_exceptions\' to TRUE.');
        $this->extension->load([$config], $container);
        $this->assertFalse(DoctrineEncryptExtension::$wrapExceptions);
    }

    /**
     * @group legacy
     */
    public function testWrapExceptionsDoesNotTriggerDeprecationWarningWhenEnabled(): void
    {
        $container = $this->createContainer();
        $config    = ['wrap_exceptions' => true];

        $this->extension->load([$config], $container);
        $this->assertTrue(DoctrineEncryptExtension::$wrapExceptions);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag(['kernel.debug' => false])
        );

        return $container;
    }
}
