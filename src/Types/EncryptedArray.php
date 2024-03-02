<?php

namespace DoctrineEncryptBundle\Types;

use DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

class EncryptedArray extends TextType
{
    use DoctrineEncrypt;

    const TYPE = 'encrypted_array';

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $this->service->decrypt ('simple_array', $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        return $this->service->encrypt ('simple_array', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
