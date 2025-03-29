<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

class EncryptedJSON extends TextType
{
    use DoctrineEncrypt;

    public const TYPE = 'encrypted_json';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->service->decrypt('json', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->service->encrypt('json', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
