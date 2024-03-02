<?php

namespace DoctrineEncryptBundle\Types;

use DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class EncryptedDateTime extends StringType
{
    use DoctrineEncrypt;

    const TYPE = 'encrypted_datetime';

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $this->service->decrypt ('datetime', $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return $this->service->encrypt ('datetime', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
