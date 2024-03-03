<?php

namespace DoctrineEncryptBundle\Tests\Unit\DependencyInjection;

use DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\ExpressionLanguage\Expression;

class DoctrineEncryptExtensionTest extends TestCase
{
    /**
     * @var DoctrineEncryptExtension
     */
    private $extension;

    private $temporaryDirectory;

    protected function setUp(): void
    {
        $this->extension = new DoctrineEncryptExtension();
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

        $this->assertSame(HaliteEncryptor::class, $container->getParameter('doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigLoadHalite(): void
    {
        $container = $this->createContainer();
        $config = [
            'encryptor_class' => 'Halite',
        ];
        $this->extension->load([$config], $container);

        $this->assertSame(HaliteEncryptor::class, $container->getParameter('doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigLoadDefuse(): void
    {
        $container = $this->createContainer();

        $config = [
            'encryptor_class' => 'Defuse',
        ];
        $this->extension->load([$config], $container);

        $this->assertSame(DefuseEncryptor::class, $container->getParameter('doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigLoadCustomEncryptor(): void
    {
        $container = $this->createContainer();
        $config = [
            'encryptor_class' => self::class,
        ];
        $this->extension->load([$config], $container);

        $this->assertSame(self::class, $container->getParameter('doctrine_encrypt.encryptor_class_name'));
    }

    public function testConfigImpossibleToUseSecretAndSecret_directory_path(): void
    {
        $container = $this->createContainer();
        $config = [
            'secret' => 'my-secret',
            'secret_directory_path' => 'var',
        ];

        $this->expectException(\InvalidArgumentException::class);

        $this->extension->load([$config], $container);
    }

    public function testConfigUseSecret(): void
    {
        $container = $this->createContainer();
        $config = [
            'secret' => 'my-secret',
        ];
        $this->extension->load([$config], $container);

        $this->assertIsString($container->getParameter('doctrine_encrypt.secret'));
        $this->assertStringNotContainsString('Halite',$container->getParameter('doctrine_encrypt.secret'));
        $this->assertStringNotContainsString('.key',$container->getParameter('doctrine_encrypt.secret'));
        $this->assertEquals('my-secret',$container->getParameter('doctrine_encrypt.secret'));
    }

    public function testHaliteSecretIsCreatedWhenSecretFileDoesNotExistAndSecretCreationIsEnabled(): void
    {
        $container = $this->createContainer();
        $config = [
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => true,
        ];
        $this->extension->load([$config], $container);

        $secretArgument = $container->getDefinition('doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        $this->assertIsString($actualSecret);
        $actualSecretOnDisk = file_get_contents($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.Halite.key');
        $this->assertEquals($actualSecret,$actualSecretOnDisk);

        try {
            KeyFactory::importEncryptionKey(new HiddenString($actualSecret));
        } catch (\Throwable $e) {
            $this->fail('Generated key is not valid');
        }
    }

    public function testDefuseSecretIsCreatedWhenSecretFileDoesNotExistAndSecretCreationIsEnabled(): void
    {
        $container = $this->createContainer();
        $config = [
            'encryptor_class'          => 'Defuse',
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => true,
        ];
        $this->extension->load([$config], $container);

        $secretArgument = $container->getDefinition('doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        $this->assertIsString($actualSecret);
        $actualSecretOnDisk = file_get_contents($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.Defuse.key');
        $this->assertEquals($actualSecret,$actualSecretOnDisk);

        if (strlen(hex2bin($actualSecret)) !== 255) {
            $this->fail('Generated key is not valid');
        }
    }

    public function testSecretIsNotCreatedWhenSecretFileDoesNotExistAndSecretCreationIsNotEnabled(): void
    {
        $container = $this->createContainer();
        $config = [
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => false,
        ];
        $this->extension->load([$config], $container);

        $this->expectException(\RuntimeException::class);
        if (method_exists($this,'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/DoctrineEncryptBundle: Unable to create secret.*/');
        } else {
            $this->markAsRisky('Unable to see if the exception matches the actual message');
        }


        $secretArgument = $container->getDefinition('doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $container->resolveServices($secretArgument);
        }
    }

    public function testSecretsAreReadFromFile(): void
    {
        // Create secret
        $expectedSecret = 'my-secret';
        file_put_contents($this->temporaryDirectory.'/.Halite.key',$expectedSecret);

        $container = $this->createContainer();
        $config = [
            'secret_directory_path'    => $this->temporaryDirectory,
            'enable_secret_generation' => false,
        ];
        $this->extension->load([$config], $container);

        $secretArgument = $container->getDefinition('doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        $this->assertIsString($actualSecret);
        $this->assertEquals($expectedSecret,$actualSecret);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag(['kernel.debug' => false])
        );

        return $container;
    }
}
