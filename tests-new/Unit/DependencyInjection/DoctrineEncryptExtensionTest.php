<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Unit\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\VersionTester;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
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

        \Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension::wrapExceptions(false);
    }

    public function testConfigLoadHaliteByDefault(): void
    {
        $container = $this->createContainer();
        $this->extension->load([[]], $container);

        $this->assertSame(HaliteEncryptor::class, $container->getParameter('doctrine_encrypt.encryptor.class_name'));
    }

    public function testConfigLoadHalite(): void
    {
        $container = $this->createContainer();
        $config    = [
            'encryptor_class' => 'Halite',
        ];
        $this->extension->load([$config], $container);

        $this->assertSame(HaliteEncryptor::class, $container->getParameter('doctrine_encrypt.encryptor.class_name'));
    }

    public function testConfigLoadDefuse(): void
    {
        $container = $this->createContainer();

        $config = [
            'encryptor_class' => 'Defuse',
        ];
        $this->extension->load([$config], $container);

        $this->assertSame(DefuseEncryptor::class, $container->getParameter('doctrine_encrypt.encryptor.class_name'));
    }

    public function testConfigLoadCustomEncryptor(): void
    {
        $container = $this->createContainer();
        $config    = [
            'encryptor_class' => self::class,
        ];
        $this->extension->load([$config], $container);

        $this->assertSame(self::class, $container->getParameter('doctrine_encrypt.encryptor.class_name'));
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

        $this->assertIsString($container->getParameter('doctrine_encrypt.secret'));
        $this->assertStringNotContainsString('Halite', $container->getParameter('doctrine_encrypt.secret'));
        $this->assertStringNotContainsString('.key', $container->getParameter('doctrine_encrypt.secret'));
        $this->assertEquals('my-secret', $container->getParameter('doctrine_encrypt.secret'));
    }

    public function testHaliteSecretIsCreatedWhenSecretFileDoesNotExistAndSecretCreationIsEnabled(): void
    {
        $container = $this->createContainer();
        $config    = [
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
        $this->assertEquals($actualSecret, $actualSecretOnDisk);

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

        $secretArgument = $container->getDefinition('doctrine_encrypt.encryptor')->getArgument(0);
        if ($secretArgument instanceof Expression) {
            $actualSecret = $container->resolveServices($secretArgument);
        } else {
            $actualSecret = $secretArgument;
        }
        $this->assertIsString($actualSecret);
        $actualSecretOnDisk = file_get_contents($this->temporaryDirectory.DIRECTORY_SEPARATOR.'.Defuse.key');
        $this->assertEquals($actualSecret, $actualSecretOnDisk);

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
        file_put_contents($this->temporaryDirectory.'/.Halite.key', $expectedSecret);

        $container = $this->createContainer();
        $config    = [
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
        $this->assertEquals($expectedSecret, $actualSecret);
    }

    /**
     * @runInSeparateProcess
     * @group legacy
     */
    public function testWrapExceptionsTriggersDeprecationWarningWhenNotDefiningTheOption(): void
    {
        $container = $this->createContainer();
        $config    = [];

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.2: Starting from 6.0, all exceptions thrown by this library will be wrapped by \DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting \'doctrine_encrypt.wrap_exceptions\' to TRUE.');
        $this->extension->load([$config], $container);
        $this->assertFalse(DoctrineEncryptExtension::wrapExceptions());
    }

    /**
     * @runInSeparateProcess
     * @group legacy
     */
    public function testWrapExceptionsTriggersDeprecationWarningWhenDisabled(): void
    {
        $container = $this->createContainer();
        $config    = ['wrap_exceptions' => false];

        $this->expectDeprecation('Since doctrineencryptbundle/doctrine-encrypt-bundle 5.4.2: Starting from 6.0, all exceptions thrown by this library will be wrapped by \DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting \'doctrine_encrypt.wrap_exceptions\' to TRUE.');
        $this->extension->load([$config], $container);
        $this->assertFalse(DoctrineEncryptExtension::wrapExceptions());
    }

    /**
     * @runInSeparateProcess
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
            $expectedParameters = array_merge(
                [$key => $value],
                $expectedParameters
            );
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
        $container->setParameter('doctrine_encrypt.secret.directory_path', $this->temporaryDirectory);

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
                'doctrine_encrypt.encryptor.class_name'     => HaliteEncryptor::class,
                'doctrine_encrypt.supported_encryptors'     => [
                    'halite' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor::class,
                    'defuse' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor::class,
                ],
                'doctrine_encrypt.secret.enable_generation' => true,
                'doctrine_encrypt.secret.directory_path'    => '%kernel.project_dir%',
            ],
            [
                'doctrine_encrypt.command.decrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_status'   => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'doctrine_encrypt.encryptor'                => '%doctrine_encrypt.encryptor.class_name%',
                'doctrine_encrypt.secret.factory'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'doctrine_encrypt.orm.subscriber'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
            ],
            [
                'doctrine_encrypt.annotations.reader'  => 'annotations.reader',
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
                'doctrine_encrypt.encryptor.class_name' => HaliteEncryptor::class,
                'doctrine_encrypt.supported_encryptors'     => [
                    'halite' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor::class,
                    'defuse' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor::class,
                ],
                'doctrine_encrypt.secret'               => ''
            ],
            [
                'doctrine_encrypt.command.decrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_status'   => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'doctrine_encrypt.encryptor'                => '%doctrine_encrypt.encryptor.class_name%',
                'doctrine_encrypt.orm.subscriber'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
            ],
            [
                'doctrine_encrypt.annotations.reader'  => 'annotations.reader',
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
                'doctrine_encrypt.encryptor.class_name'     => HaliteEncryptor::class,
                'doctrine_encrypt.supported_encryptors'     => [
                    'halite' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor::class,
                    'defuse' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor::class,
                ],
                'doctrine_encrypt.secret.enable_generation' => true,
                'doctrine_encrypt.secret.directory_path'    => '%kernel.project_dir%',
            ],
            [
                'doctrine_encrypt.command.decrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_status'   => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'doctrine_encrypt.encryptor'                => '%doctrine_encrypt.encryptor.class_name%',
                'doctrine_encrypt.secret.factory'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'doctrine_encrypt.orm.subscriber'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'doctrine_encrypt.attributes.reader'        => \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader::class,
                'doctrine_encrypt.annotations.reader'       => \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader::class,
            ],
            [
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
                'doctrine_encrypt.encryptor.class_name'     => HaliteEncryptor::class,
                'doctrine_encrypt.supported_encryptors'     => [
                    'halite' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor::class,
                    'defuse' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor::class,
                ],
                'doctrine_encrypt.secret.enable_generation' => true,
                'doctrine_encrypt.secret.directory_path'    => '%kernel.project_dir%',
            ],
            [
                'doctrine_encrypt.command.decrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_status'   => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'doctrine_encrypt.encryptor'                => '%doctrine_encrypt.encryptor.class_name%',
                'doctrine_encrypt.secret.factory'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'doctrine_encrypt.orm.subscriber'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'doctrine_encrypt.attributes.reader'        => \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            ],
            [
                'doctrine_encrypt.annotations.reader'  => 'doctrine_encrypt.attributes.reader',
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
                'doctrine_encrypt.encryptor.class_name'     => HaliteEncryptor::class,
                'doctrine_encrypt.supported_encryptors'     => [
                    'halite' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor::class,
                    'defuse' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor::class,
                ],
                'doctrine_encrypt.secret.enable_generation' => true,
                'doctrine_encrypt.secret.directory_path'    => '%kernel.project_dir%',
            ],
            [
                'doctrine_encrypt.command.decrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_status'   => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'doctrine_encrypt.encryptor'                => '%doctrine_encrypt.encryptor.class_name%',
                'doctrine_encrypt.secret.factory'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Factories\SecretFactory::class,
                'doctrine_encrypt.orm.subscriber'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'doctrine_encrypt.attributes.reader'        => \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            ],
            [
                'doctrine_encrypt.annotations.reader'  => 'doctrine_encrypt.attributes.reader',
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
                'doctrine_encrypt.encryptor.class_name' => HaliteEncryptor::class,
                'doctrine_encrypt.supported_encryptors' => [
                    'halite' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor::class,
                    'defuse' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor::class,
                ],
                'doctrine_encrypt.secret'               => '',
            ],
            [
                'doctrine_encrypt.command.decrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_database' => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                'doctrine_encrypt.command.encrypt_status'   => \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                'doctrine_encrypt.encryptor'                => '%doctrine_encrypt.encryptor.class_name%',
                'doctrine_encrypt.orm.subscriber'           => \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                'doctrine_encrypt.attributes.reader'        => \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            ],
            [
                'doctrine_encrypt.annotations.reader' => 'doctrine_encrypt.attributes.reader',
            ],
        ];
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => sys_get_temp_dir(),
            ])
        );

        $container->setDefinition('annotations.reader', new Definition(AnnotationReader::class));

        return $container;
    }
}
