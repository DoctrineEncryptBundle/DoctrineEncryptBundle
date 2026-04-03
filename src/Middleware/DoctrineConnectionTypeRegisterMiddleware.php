<?php

namespace Ambta\DoctrineEncryptBundle\Middleware;

use Ambta\DoctrineEncryptBundle\Service\EncryptServiceAwareInterface;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

final class DoctrineConnectionTypeRegisterMiddleware implements Middleware
{
    public function __construct(private EncryptServiceAwareInterface $encryptService)
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new DoctrineConnectionTypeRegisterDriver($driver, $this->encryptService);
    }
}
