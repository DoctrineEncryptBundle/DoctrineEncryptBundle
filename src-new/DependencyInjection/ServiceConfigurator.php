<?php

declare(strict_types=1);

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension as AmbtaExtension;
use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension as BundleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal
 */
final class ServiceConfigurator
{
    /** @var VersionTester */
    private $versionTester;

    /** @var bool */
    private $useNewNames;

    /** @var int */
    private $nameIndex;

    private const NAMES = [
        'parameters' => [
            'secret' => [
                'ambta_doctrine_encrypt.secret',
                'doctrine_encrypt.secret',
            ],
            'encryptor_class_name' => [
                'ambta_doctrine_encrypt.encryptor_class_name',
                'doctrine_encrypt.encryptor.class_name',
            ],
            'enable_secret_generation' => [
                'ambta_doctrine_encrypt.enable_secret_generation',
                'doctrine_encrypt.secret.enable_generation',
            ],
            'secret_directory_path' => [
                'ambta_doctrine_encrypt.secret_directory_path',
                'doctrine_encrypt.secret.directory_path',
            ],
            'supported_encryptors' => [
                'ambta_doctrine_encrypt.supported_encryptors',
                'doctrine_encrypt.supported_encryptors',
            ],
        ],
        'services' => [
            'encryptor' => [
                'ambta_doctrine_encrypt.encryptor',
                'doctrine_encrypt.encryptor',
            ],
            'secret_factory' =>  [
                'ambta_doctrine_encrypt.secret_factory',
                'doctrine_encrypt.secret.factory',
            ],
            'annotation_reader' => [
                'ambta_doctrine_annotation_reader',
                'doctrine_encrypt.annotations.reader',
            ],
            'attribute_reader' => [
                'ambta_doctrine_attribute_reader',
                'doctrine_encrypt.attributes.reader',
            ],
            'orm_subscriber' => [
                'ambta_doctrine_encrypt.orm_subscriber',
                'doctrine_encrypt.orm.subscriber',
            ],
            'command.decrypt.database' => [
                'ambta_doctrine_encrypt.command.decrypt.database',
                'doctrine_encrypt.command.decrypt_database',
            ],
            'command.encrypt.database' => [
                'ambta_doctrine_encrypt.command.encrypt.database',
                'doctrine_encrypt.command.encrypt_database',
            ],
            'command.encrypt.status' => [
                'ambta_doctrine_encrypt.command.encrypt.status',
                'doctrine_encrypt.command.encrypt_status',
            ],
        ],
    ];

    public function __construct(
        VersionTester $versionTester,
        bool $useNewNames
    )
    {
        $this->versionTester = $versionTester;
        $this->useNewNames   = $useNewNames;

        if ($this->useNewNames) {
            $this->nameIndex = 1;
        } else {
            $this->nameIndex = 0;
        }
    }

    private function setParameter(ContainerBuilder $container, string $name, $value): void
    {
        $container->setParameter($this->getParameterName($name), $value);
    }

    private function getParameterName(string $name): string
    {
        return self::NAMES['parameters'][$name][$this->nameIndex];
    }

    private function getServiceName(string $name): string
    {
        return self::NAMES['services'][$name][$this->nameIndex];
    }

    private function registerService(ContainerBuilder $container, string $name, string $oldClass, string $newClass): Definition
    {
        return $container
            ->register(
                $this->getServiceName($name),
                $this->useNewNames ? $newClass : $oldClass
            );
    }

    public function configure(array $config, ContainerBuilder $container): void
    {
        $supportedEncryptors = $this->useNewNames
            ? BundleExtension::SupportedEncryptorClasses
            : AmbtaExtension::SupportedEncryptorClasses
        ;

        // If empty encryptor class, use Halite encryptor
        if (array_key_exists($config['encryptor_class'], $supportedEncryptors)) {
            $config['encryptor_class_full'] = $supportedEncryptors[$config['encryptor_class']];
        } else {
            $config['encryptor_class_full'] = $config['encryptor_class'];
        }

        $this->setParameter($container, 'supported_encryptors', $supportedEncryptors);

        // Set parameters
        $this->setParameter($container, 'encryptor_class_name', $config['encryptor_class_full']);

        if (isset($config['secret'])) {
            $this->setParameter($container, 'secret', $config['secret']);
        } else {
            $this->setParameter($container, 'enable_secret_generation',$config['enable_secret_generation']);
            $this->setParameter($container, 'secret_directory_path', $config['secret_directory_path']);
        }

        $this->defineDefaultServices($container);

        if (!isset($config['secret'])) {
            $this->defineServicesUsingSecretFactory($container);
        } else {
            $this->defineServicesUsingSecret($container);
        }

        // Symfony 1-4
        // Sanity-check since this should be blocked by composer.json
        if (Kernel::MAJOR_VERSION < 5 || (Kernel::MAJOR_VERSION === 5 && Kernel::MINOR_VERSION < 4)) {
            throw new \RuntimeException('doctrineencryptbundle/doctrine-encrypt-bundle expects symfony-version >= 5.4!');
        }

        // Symfony 5-6
        if (!$this->versionTester->isSymfony7OrHigher()) {
            // PHP 7.x (no attributes)
            if (!$this->versionTester->isPhp8OrHigher()) {
                $this->defineServicesUsingAnnotations($container);
                // PHP 8.x (annotations and attributes)
            } else {
                // Doctrine 3.0 - no annotations
                if ($this->versionTester->doctrineOrmIsVersion3()) {
                    $this->defineServicesUsingAttributes($container);
                } else {
                    $this->defineServicesUsingAnnotationsAndAttributes($container);
                }
            }
            // Symfony 7 (only attributes)
        } else {
            $this->defineServicesUsingAttributes($container);
        }

        // Wrap exceptions
        if ($config['wrap_exceptions']) {
            AmbtaExtension::wrapExceptions(true);
        } else {
            trigger_deprecation(
                'doctrineencryptbundle/doctrine-encrypt-bundle',
                '5.4.2',
                $this->useNewNames
                    ? <<<EOF
Starting from 6.0, all exceptions thrown by this library will be wrapped by \DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting 'doctrine_encrypt.wrap_exceptions' to TRUE.
EOF
                    : <<<EOF
Starting from 6.0, all exceptions thrown by this library will be wrapped by \Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException or a child-class of it.
You can start using these exceptions today by setting 'ambta_doctrine_encrypt.wrap_exceptions' to TRUE.
EOF
            );
        }
    }

    private function defineDefaultServices(ContainerBuilder $container): void
    {
        if (!$this->useNewNames) {
            $container
                ->setAlias(
                    'ambta_doctrine_encrypt.subscriber',
                    'ambta_doctrine_encrypt.orm_subscriber'
                )
                ->setDeprecated(
                    'doctrineencryptbundle/doctrine-encrypt-bundle',
                    '5.5',
                    '%alias_id% will be removed, use the new listener "doctrine_encrypt.orm_subscriber'
                );
        }

        $this
            ->registerService(
                $container,
                'command.decrypt.database',
                \Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand::class
            )
            ->addTag('console.command')
            ->setArguments([
                new Reference('doctrine.orm.entity_manager'),
                new Reference($this->getServiceName('annotation_reader')),
                new Reference($this->getServiceName('orm_subscriber')),
                new Parameter($this->getParameterName('supported_encryptors')),
            ])
        ;

        $this
            ->registerService(
                $container,
                'command.encrypt.database',
                \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand::class
            )
            ->addTag('console.command')
            ->setArguments([
                new Reference('doctrine.orm.entity_manager'),
                new Reference($this->getServiceName('annotation_reader')),
                new Reference($this->getServiceName('orm_subscriber')),
                new Parameter($this->getParameterName('supported_encryptors')),
            ])
        ;
        $this
            ->registerService(
                $container,
                'command.encrypt.status',
                \Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand::class
            )
            ->addTag('console.command')
            ->setArguments([
                new Reference('doctrine.orm.entity_manager'),
                new Reference($this->getServiceName('annotation_reader')),
                new Reference($this->getServiceName('orm_subscriber')),
                new Parameter($this->getParameterName('supported_encryptors')),
            ])
        ;
    }

    private function defineServicesUsingSecret(ContainerBuilder $container): void
    {
        $container
            ->register(
                $this->getServiceName('encryptor'),
                sprintf('%%%s%%', $this->getParameterName('encryptor_class_name'))
            )
            ->setArguments([
                new Parameter($this->getParameterName('secret')),
            ])
        ;
    }

    private function defineServicesUsingSecretFactory(ContainerBuilder $container): void
    {
        $container
            ->register(
                $this->getServiceName('encryptor'),
                sprintf('%%%s%%', $this->getParameterName('encryptor_class_name'))
            )
            ->setArguments([
                new Expression(
                    sprintf(
                        'service("%s").getSecret(parameter("%s"))',
                        $this->getServiceName('secret_factory'),
                        $this->getParameterName('encryptor_class_name')
                    )
                ),
            ])
        ;

        $this
            ->registerService(
                $container,
                'secret_factory',
                \Ambta\DoctrineEncryptBundle\Factories\SecretFactory::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Factories\SecretFactory::class
            )
            ->setArguments([
                new Parameter($this->getParameterName('secret_directory_path')),
                new Parameter($this->getParameterName('enable_secret_generation')),
                new Parameter($this->getParameterName('supported_encryptors')),
            ])
        ;
    }

    private function defineServicesUsingAnnotations(ContainerBuilder $container): void
    {
        $container->setAlias($this->getServiceName('annotation_reader'), 'annotations.reader');

        $this
            ->registerService(
                $container,
                'orm_subscriber',
                \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class
            )
            ->setArguments([
                new Reference($this->getServiceName('annotation_reader')),
                new Reference($this->getServiceName('encryptor')),
            ])
            ->addTag('doctrine.event_subscriber')
        ;
    }

    private function defineServicesUsingAttributes(ContainerBuilder $container): void
    {
        $this->registerService(
            $container,
            'attribute_reader',
            \Ambta\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader::class
        );
        $container->setAlias(
            $this->getServiceName('annotation_reader'),
            $this->getServiceName('attribute_reader')
        );
        $this
            ->registerService(
                $container,
                'orm_subscriber',
                \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class
            )
            ->setArguments([
                new Reference($this->getServiceName('attribute_reader')),
                new Reference($this->getServiceName('encryptor')),
            ])
            ->addTag('doctrine.event_listener',['event' => 'postLoad'])
            ->addTag('doctrine.event_listener',['event' => 'onFlush'])
            ->addTag('doctrine.event_listener',['event' => 'preFlush'])
            ->addTag('doctrine.event_listener',['event' => 'postFlush'])
            ->addTag('doctrine.event_listener',['event' => 'onClear'])
        ;
    }

    private function defineServicesUsingAnnotationsAndAttributes(ContainerBuilder $container): void
    {
        $this->registerService(
            $container,
            'attribute_reader',
            \Ambta\DoctrineEncryptBundle\Mapping\AttributeReader::class,
            \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader::class
        );

        $this
            ->registerService(
                $container,
                'annotation_reader',
                \Ambta\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader::class
            )
            ->setArguments([
                new Reference($this->getServiceName('attribute_reader')),
                new Reference('annotations.reader'),
                new Parameter('kernel.cache_dir'),
            ])
        ;

        $this
            ->registerService(
                $container,
                'orm_subscriber',
                \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class,
                \DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber::class
            )
            ->setArguments([
                new Reference($this->getServiceName('annotation_reader')),
                new Reference($this->getServiceName('encryptor')),
            ])
            ->addTag('doctrine.event_subscriber')
        ;
    }
}