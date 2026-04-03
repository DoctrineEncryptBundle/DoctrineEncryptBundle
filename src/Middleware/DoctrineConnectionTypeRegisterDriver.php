<?php

namespace Ambta\DoctrineEncryptBundle\Middleware;

use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Ambta\DoctrineEncryptBundle\Service\EncryptServiceAwareInterface;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Types\Type;

final class DoctrineConnectionTypeRegisterDriver extends AbstractDriverMiddleware
{
    public function __construct(Driver $wrappedDriver, private EncryptServiceAwareInterface $encryptService)
    {
        parent::__construct($wrappedDriver);

        foreach (EncryptService::ENCRYPT_TYPES as $encyptName => $encryptClass) {
            if (!Type::hasType($encyptName)) {
                Type::addType($encyptName, $encryptClass);
                $addedType = Type::getType($encyptName);
                $addedType->setEncryptService($this->encryptService);
            }
        }
    }

    public function connect(array $params): Connection
    {
        $connection = parent::connect($params);

        foreach (EncryptService::ENCRYPT_TYPES as $encyptName => $encryptClass) {
            if (class_exists(\Doctrine\DBAL\Connection\StaticServerVersionProvider::class)) {
                $databasePlatform = $this->getDatabasePlatform(new \Doctrine\DBAL\Connection\StaticServerVersionProvider($connection->getServerVersion()));
            } else {
                $databasePlatform = $this->getDatabasePlatform();
            }
            if (!$databasePlatform->hasDoctrineTypeMappingFor($encyptName)) {
                $databasePlatform->registerDoctrineTypeMapping($encyptName, $encyptName);
            }
        }

        return $connection;
    }
}
