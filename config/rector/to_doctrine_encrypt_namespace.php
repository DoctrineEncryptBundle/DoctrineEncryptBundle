<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassMethod\RenameAnnotationRector;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function(RectorConfig $rectorConfig): void
{
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\DoctrineEncryptBundle',
        'Ambta\DoctrineEncryptBundle\Configuration\Encrypted' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted',
        'Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand',
        'Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand',
        'Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand',
        'Ambta\DoctrineEncryptBundle\DependencyInjection\Configuration' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\Configuration',
        'Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension',
        'Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\EncryptorInterface',
        'Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor',
        'Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor',
        'Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException',
        'Ambta\DoctrineEncryptBundle\Exception\UnableToDecryptException' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToDecryptException',
        'Ambta\DoctrineEncryptBundle\Exception\UnableToEncryptException' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToEncryptException',
        'Ambta\DoctrineEncryptBundle\Factories\SecretFactory' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Factories\SecretFactory',
        'Ambta\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader',
        'Ambta\DoctrineEncryptBundle\Mapping\AttributeReader' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping\AttributeReader',
        'Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber' => 'DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber',
    ]);

    $rectorConfig->ruleWithConfiguration(RenameAnnotationRector::class, [
        new \Rector\Renaming\ValueObject\RenameAnnotation('Ambta\DoctrineEncryptBundle\Configuration\Encrypted','DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted'),
    ]);
};