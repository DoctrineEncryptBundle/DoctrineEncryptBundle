<?php

namespace Ambta\DoctrineEncryptBundle\Factories;

use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Ambta\DoctrineEncryptBundle\Service\EncryptServiceAwareInterface;
use Doctrine\ORM\EntityManagerInterface;

class EncryptServiceFactory
{
    public static function createEncryptService(
        string $secretDirectoryPath,
        string $enableSecretGeneration,
        string $encryptorClassName,
        EntityManagerInterface $entityManager,
        ?string $secret = null
    ): EncryptServiceAwareInterface {
        $encryptService = new EncryptService();
        $encryptService->setEntityManager($entityManager);
        if (!$enableSecretGeneration && !is_null($secret)) {
            $encryptService->setEncryptor(EncryptorFactory::createEncryptor($secret, $encryptorClassName));
        } else {
            $secretFactory = new SecretFactory($secretDirectoryPath, $enableSecretGeneration);
            $encryptService->setEncryptor(EncryptorFactory::createEncryptor($secretFactory->getSecret($encryptorClassName), $encryptorClassName));
        }

        return $encryptService;
    }
}
