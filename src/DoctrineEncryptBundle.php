<?php

namespace DoctrineEncryptBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use DoctrineEncryptBundle\Types\Encrypted;
use Doctrine\DBAL\Types\Type;
use DoctrineEncryptBundle\Types\EncryptedArray;
use DoctrineEncryptBundle\Types\EncryptedDateTime;
use DoctrineEncryptBundle\Types\EncryptedJSON;

class DoctrineEncryptBundle extends Bundle
{
    public const ENCRYPT_TYPES =
        [
            'encrypted' => Encrypted::class,
            'encrypted_datetime' => EncryptedDateTime::class,
            'encrypted_json' => EncryptedJSON::class,
            'encrypted_array' => EncryptedArray::class
        ];

    public function boot(): void
    {
        $connections = $this->container->get('doctrine')->getConnections();
        $service = $this->container->get('doctrine_encrypt.encrypt_service');
        foreach (self::ENCRYPT_TYPES as $encyptName => $encryptClass)
        {
            if (! Type::hasType($encyptName))
            {
                Type::addType($encyptName, $encryptClass);
                /** @var \DoctrineEncryptBundle\Traits\DoctrineEncrypt $addedType */
                $addedType = Type::getType($encyptName);
                $addedType->setService($service);
            }

            foreach ($connections as $connectionName => $connection)
            {
                $databasePlatform = $connection->getDatabasePlatform();
                if (! $databasePlatform->hasDoctrineTypeMappingFor($encyptName))
                {
                    $databasePlatform->registerDoctrineTypeMapping($encyptName,$encyptName);
                }
            }
        }      
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineEncryptExtension();
    }
}
