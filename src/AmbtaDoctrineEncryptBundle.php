<?php

namespace Ambta\DoctrineEncryptBundle;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Types\Encrypted;
use Ambta\DoctrineEncryptBundle\Types\EncryptedArray;
use Ambta\DoctrineEncryptBundle\Types\EncryptedDateTime;
use Ambta\DoctrineEncryptBundle\Types\EncryptedJSON;
use Ambta\DoctrineEncryptBundle\Types\PHP7\Encrypted as PHP7Encrypted;
use Ambta\DoctrineEncryptBundle\Types\PHP7\EncryptedArray as PHP7EncryptedArray;
use Ambta\DoctrineEncryptBundle\Types\PHP7\EncryptedDateTime as PHP7EncryptedDateTime;
use Ambta\DoctrineEncryptBundle\Types\PHP7\EncryptedJSON as PHP7EncryptedJSON;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AmbtaDoctrineEncryptBundle extends Bundle
{
    public const ENCRYPT_TYPES = [
        'encrypted'          => Encrypted::class,
        'encrypted_datetime' => EncryptedDateTime::class,
        'encrypted_json'     => EncryptedJSON::class,
        'encrypted_array'    => EncryptedArray::class
    ];

    public const ENCRYPT_TYPES_PHP7 = [
        'encrypted'          => PHP7Encrypted::class,
        'encrypted_datetime' => PHP7EncryptedDateTime::class,
        'encrypted_json'     => PHP7EncryptedJSON::class,
        'encrypted_array'    => PHP7EncryptedArray::class
    ];

    public function boot(): void
    {
        $connections = $this->container->get('doctrine')->getConnections();
        $service     = $this->container->get('ambta_doctrine_encrypt.encrypt_service');
        $types       = self::ENCRYPT_TYPES;
        if (PHP_VERSION_ID < 80000) {
            $types = self::ENCRYPT_TYPES_PHP7;
        }
        foreach ($types as $encyptName => $encryptClass) {
            if (!Type::hasType($encyptName)) {
                Type::addType($encyptName, $encryptClass);
                /** @var Traits\DoctrineEncrypt $addedType */
                $addedType = Type::getType($encyptName);
                $addedType->setService($service);
            }

            foreach ($connections as $connectionName => $connection) {
                $databasePlatform = $connection->getDatabasePlatform();
                if (!$databasePlatform->hasDoctrineTypeMappingFor($encyptName)) {
                    $databasePlatform->registerDoctrineTypeMapping($encyptName, $encyptName);
                }
            }
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineEncryptExtension();
    }
}
