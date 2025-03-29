<?php

namespace Ambta\DoctrineEncryptBundle\Types\PHP7;

use Ambta\DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class EncryptedDateTime extends StringType
{
    use DoctrineEncrypt;

    public const TYPE = 'encrypted_datetime';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $this->service->decrypt('datetime', $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->service->encrypt('datetime', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
