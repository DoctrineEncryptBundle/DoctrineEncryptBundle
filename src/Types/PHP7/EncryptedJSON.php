<?php

namespace Ambta\DoctrineEncryptBundle\Types\PHP7;

use Ambta\DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

class EncryptedJSON extends TextType
{
    use DoctrineEncrypt;

    public const TYPE = 'encrypted_json';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $this->service->decrypt('json', $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->service->encrypt('json', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
