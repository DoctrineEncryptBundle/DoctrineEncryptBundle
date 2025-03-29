<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

class EncryptedArray extends TextType
{
    use DoctrineEncrypt;

    public const TYPE = 'encrypted_array';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->service->decrypt('simple_array', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->service->encrypt('simple_array', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
