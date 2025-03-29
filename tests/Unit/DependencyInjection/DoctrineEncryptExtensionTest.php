<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Unit\DependencyInjection;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\VersionTester;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Filesystem\Filesystem;

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
        $this->extension          = new DoctrineEncryptExtension(new VersionTester());
        $this->temporaryDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.sha1(mt_rand());
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->temporaryDirectory);

        DoctrineEncryptExtension::wrapExceptions(false);
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
     * @runInSeparateProcess
     *
     * @group legacy
     */
    public function testWrapExceptionsTriggersDeprecationWarningWhenNotDefiningTheOption(): void
    {
        $container = $this->createContainer();
        $config    = [];

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.2: Starting from 6.0, all exceptions thrown by this library will be wrapped by \Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting \'ambta_doctrine_encrypt.wrap_exceptions\' to TRUE.');
        $this->extension->load([$config], $container);
        $this->assertFalse(DoctrineEncryptExtension::wrapExceptions());
    }

    /**
     * @runInSeparateProcess
     *
     * @group legacy
     */
    public function testWrapExceptionsTriggersDeprecationWarningWhenDisabled(): void
    {
        $container = $this->createContainer();
        $config    = ['wrap_exceptions' => false];

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.2: Starting from 6.0, all exceptions thrown by this library will be wrapped by \Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting \'ambta_doctrine_encrypt.wrap_exceptions\' to TRUE.');
        $this->extension->load([$config], $container);
        $this->assertFalse(DoctrineEncryptExtension::wrapExceptions());
    }

    /**
     * @runInSeparateProcess
     *
     * @group legacy
     */
    public function testWrapExceptionsDoesNotTriggerDeprecationWarningWhenEnabled(): void
    {
        $container = $this->createContainer();
        $config    = ['wrap_exceptions' => true];

        $this->expectDeprecation('');
        $this->extension->load([$config], $container);
        $this->assertTrue(DoctrineEncryptExtension::wrapExceptions());
    }

    /**
     * @dataProvider provideConfigLoadsCorrectServicesAndParametersCases
     */
    public function testConfigLoadsCorrectServicesAndParameters(
        array $config,
        array $mockedVersions,
        array $expectedParameters,
        array $expectedServices,
        array $expectedAliases
    ): void {
        $container = $this->createContainer();

        // Default from setup
        foreach ($container->getParameterBag()->all() as $key => $value) {
            $expectedParameters[$key] = $value;
        }
        foreach ($container->getDefinitions() as $id => $definition) {
            $expectedServices[$id] = $definition->getClass();
        }

        $originalExpectedServiceIds = $container->getServiceIds();

        $versionTester = $this->createMock(VersionTester::class);
        foreach ($mockedVersions as $method => $response) {
            $versionTester->method($method)->willReturn($response);
        }

        $extension = new DoctrineEncryptExtension($versionTester);

        $extension->load([$config], $container);

        $this->assertEqualsCanonicalizing($expectedParameters, $container->getParameterBag()->all());

        $expectedServiceIds = array_unique(array_merge(
            $originalExpectedServiceIds,
            array_keys($expectedServices),
            array_keys($expectedAliases)
        ));

        $this->assertEqualsCanonicalizing($expectedServiceIds, $container->getServiceIds());

        foreach ($expectedServices as $expectedService => $expectedValue) {
            $this->assertTrue($container->has($expectedService));
            $this->assertEquals($expectedValue, $container->getDefinition($expectedService)->getClass(), $expectedService);
        }

        foreach ($expectedAliases as $expectedAlias => $expectedValue) {
            $this->assertTrue($container->has($expectedAlias));
            $this->assertEquals($expectedValue, (string) $container->getAlias($expectedAlias));
        }

        // Mock additional services
        $container->set('doctrine.orm.entity_manager', $this->createMock(EntityManagerInterface::class));
        $container->setParameter('kernel.project_dir', $this->temporaryDirectory);
        $container->setParameter('kernel.cache_dir', $this->temporaryDirectory);

        // set directory path to writable directory
        $container->setParameter('ambta_doctrine_encrypt.secret_directory_path', $this->temporaryDirectory);

        // Assert all services are gettable
        foreach ($expectedServices as $expectedService => $class) {
            $this->assertNotNull($container->get($expectedService));
        }
    }

    public static function provideConfigLoadsCorrectServicesAndParametersCases(): iterable
    {
        yield 'empty-sf5-php7-orm2' => [
            [],
            [
                'isSymfony7OrHigher'    => false,
                'isPhp8OrHigher'        => false,
                'doctrineOrmIsVersion3' => false,
            ],
            [
                'ambta_doctrine_encrypt.encryptor_class_name' => HaliteEncryptor::class,
                'ambta_doctrine_encrypt.supported_encryptors' => [
                    'halite' => DefuseEncryptor::class,
                    'defuse' => HaliteEncryptor::class,
                ],
                'ambta_doctrine_encrypt.enable_secret_generation' => true,
                'ambta_doctrine_encrypt.secret_directory_path'    => '%kernel.project_dir%',
            ],
            [
                'ambta_doctrine_encrypt.command.decrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.status'   => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'ambta_doctrine_encrypt.encryptor'                => '%ambta_doctrine_encrypt.encryptor_class_name%',
                'ambta_doctrine_encrypt.secret_factory'           => \Ambta\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'ambta_doctrine_encrypt.orm_subscriber'           => \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
            ],
            [
                'ambta_doctrine_encrypt.subscriber' => 'ambta_doctrine_encrypt.orm_subscriber',
                'ambta_doctrine_annotation_reader'  => 'annotations.reader',
            ],
        ];

        yield 'secret-sf5-php7-orm2' => [
            [
                'secret' => '',
            ],
            [
                'isSymfony7OrHigher'    => false,
                'isPhp8OrHigher'        => false,
                'doctrineOrmIsVersion3' => false,
            ],
            [
                'ambta_doctrine_encrypt.encryptor_class_name' => HaliteEncryptor::class,
                'ambta_doctrine_encrypt.supported_encryptors' => [
                    'halite' => DefuseEncryptor::class,
                    'defuse' => HaliteEncryptor::class,
                ],
                'ambta_doctrine_encrypt.secret' => ''
            ],
            [
                'ambta_doctrine_encrypt.command.decrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.status'   => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'ambta_doctrine_encrypt.encryptor'                => '%ambta_doctrine_encrypt.encryptor_class_name%',
                'ambta_doctrine_encrypt.orm_subscriber'           => \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
            ],
            [
                'ambta_doctrine_encrypt.subscriber' => 'ambta_doctrine_encrypt.orm_subscriber',
                'ambta_doctrine_annotation_reader'  => 'annotations.reader',
            ],
        ];

        yield 'empty-sf5-php8-orm2' => [
            [],
            [
                'isSymfony7OrHigher'    => false,
                'isPhp8OrHigher'        => true,
                'doctrineOrmIsVersion3' => false,
            ],
            [
                'ambta_doctrine_encrypt.encryptor_class_name' => HaliteEncryptor::class,
                'ambta_doctrine_encrypt.supported_encryptors' => [
                    'halite' => DefuseEncryptor::class,
                    'defuse' => HaliteEncryptor::class,
                ],
                'ambta_doctrine_encrypt.enable_secret_generation' => true,
                'ambta_doctrine_encrypt.secret_directory_path'    => '%kernel.project_dir%',
            ],
            [
                'ambta_doctrine_encrypt.command.decrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.status'   => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'ambta_doctrine_encrypt.encryptor'                => '%ambta_doctrine_encrypt.encryptor_class_name%',
                'ambta_doctrine_encrypt.secret_factory'           => \Ambta\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'ambta_doctrine_encrypt.orm_subscriber'           => \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'ambta_doctrine_attribute_reader'                 => \Ambta\DoctrineEncryptBundle\Mapping\AttributeReader::class,
                'ambta_doctrine_annotation_reader'                => \Ambta\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader::class,
            ],
            [
                'ambta_doctrine_encrypt.subscriber' => 'ambta_doctrine_encrypt.orm_subscriber',
            ],
        ];

        yield 'empty-sf5-php8-orm3' => [
            [],
            [
                'isSymfony7OrHigher'    => false,
                'isPhp8OrHigher'        => true,
                'doctrineOrmIsVersion3' => true,
            ],
            [
                'ambta_doctrine_encrypt.encryptor_class_name' => HaliteEncryptor::class,
                'ambta_doctrine_encrypt.supported_encryptors' => [
                    'halite' => DefuseEncryptor::class,
                    'defuse' => HaliteEncryptor::class,
                ],
                'ambta_doctrine_encrypt.enable_secret_generation' => true,
                'ambta_doctrine_encrypt.secret_directory_path'    => '%kernel.project_dir%',
            ],
            [
                'ambta_doctrine_encrypt.command.decrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.status'   => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'ambta_doctrine_encrypt.encryptor'                => '%ambta_doctrine_encrypt.encryptor_class_name%',
                'ambta_doctrine_encrypt.secret_factory'           => \Ambta\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'ambta_doctrine_encrypt.orm_subscriber'           => \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'ambta_doctrine_attribute_reader'                 => \Ambta\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            ],
            [
                'ambta_doctrine_encrypt.subscriber' => 'ambta_doctrine_encrypt.orm_subscriber',
                'ambta_doctrine_annotation_reader'  => 'ambta_doctrine_attribute_reader',
            ],
        ];

        yield 'empty-sf7-php8-orm3' => [
            [],
            [
                'isSymfony7OrHigher'    => true,
                'isPhp8OrHigher'        => true,
                'doctrineOrmIsVersion3' => true,
            ],
            [
                'ambta_doctrine_encrypt.encryptor_class_name' => HaliteEncryptor::class,
                'ambta_doctrine_encrypt.supported_encryptors' => [
                    'halite' => DefuseEncryptor::class,
                    'defuse' => HaliteEncryptor::class,
                ],
                'ambta_doctrine_encrypt.enable_secret_generation' => true,
                'ambta_doctrine_encrypt.secret_directory_path'    => '%kernel.project_dir%',
            ],
            [
                'ambta_doctrine_encrypt.command.decrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.status'   => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'ambta_doctrine_encrypt.encryptor'                => '%ambta_doctrine_encrypt.encryptor_class_name%',
                'ambta_doctrine_encrypt.secret_factory'           => \Ambta\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'ambta_doctrine_encrypt.orm_subscriber'           => \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'ambta_doctrine_attribute_reader'                 => \Ambta\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            ],
            [
                'ambta_doctrine_encrypt.subscriber' => 'ambta_doctrine_encrypt.orm_subscriber',
                'ambta_doctrine_annotation_reader'  => 'ambta_doctrine_attribute_reader',
            ],
        ];

        yield 'secret-sf7-php8-orm3' => [
            [
                'secret' => '',
            ],
            [
                'isSymfony7OrHigher'    => true,
                'isPhp8OrHigher'        => true,
                'doctrineOrmIsVersion3' => true,
            ],
            [
                'ambta_doctrine_encrypt.encryptor_class_name' => HaliteEncryptor::class,
                'ambta_doctrine_encrypt.supported_encryptors' => [
                    'halite' => DefuseEncryptor::class,
                    'defuse' => HaliteEncryptor::class,
                ],
                'ambta_doctrine_encrypt.secret' => '',
            ],
            [
                'ambta_doctrine_encrypt.command.decrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.database' => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'ambta_doctrine_encrypt.command.encrypt.status'   => \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'ambta_doctrine_encrypt.encryptor'                => '%ambta_doctrine_encrypt.encryptor_class_name%',
                'ambta_doctrine_encrypt.orm_subscriber'           => \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'ambta_doctrine_attribute_reader'                 => \Ambta\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            ],
            [
                'ambta_doctrine_encrypt.subscriber' => 'ambta_doctrine_encrypt.orm_subscriber',
                'ambta_doctrine_annotation_reader'  => 'ambta_doctrine_attribute_reader',
            ],
        ];
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
            ])
        );

        $container->setDefinition('annotations.reader', new Definition(AnnotationReader::class));

        return $container;
    }
}
