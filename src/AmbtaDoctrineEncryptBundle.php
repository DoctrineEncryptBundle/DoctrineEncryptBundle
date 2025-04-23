<?php

namespace Ambta\DoctrineEncryptBundle;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Factories\EncryptServiceFactory;
use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AmbtaDoctrineEncryptBundle extends Bundle
{
    public function boot(): void
    {
        $connections            = $this->container->get('doctrine')->getConnections();
        $entityManager          = $this->container->get('doctrine.orm.entity_manager');
        $secretDirectoryPath    = $this->container->getParameter('ambta_doctrine_encrypt.secret_directory_path');
        $enableSecretGeneration = $this->container->getParameter('ambta_doctrine_encrypt.enable_secret_generation');
        $encryptorClassName     = $this->container->getParameter('ambta_doctrine_encrypt.encryptor_class_name');
        $secrect                = null;
        if ($this->container->hasParameter('ambta_doctrine_encrypt.secret')) {
            $secrect = $this->container->getParameter('ambta_doctrine_encrypt.secret');
        }
        $encryptService = EncryptServiceFactory::createEncryptService($secretDirectoryPath, $enableSecretGeneration, $encryptorClassName, $entityManager, $secrect);

        foreach (EncryptService::ENCRYPT_TYPES as $encyptName => $encryptClass) {
            if (!Type::hasType($encyptName)) {
                Type::addType($encyptName, $encryptClass);
                $addedType = Type::getType($encyptName);
                $addedType->setEncryptService($encryptService);
                $addedType->setEntityManager($entityManager);
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
