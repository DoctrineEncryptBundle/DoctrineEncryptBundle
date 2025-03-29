<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class Encrypted extends StringType
{
    use DoctrineEncrypt;

    public const TYPE = 'encrypted';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->service->decrypt('string', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->service->encrypt('string', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
