<?php

namespace Ambta\DoctrineEncryptBundle\Types\PHP7;

use Ambta\DoctrineEncryptBundle\Traits\DoctrineEncrypt;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class Encrypted extends StringType
{
    use DoctrineEncrypt;

    public const TYPE = 'encrypted';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $this->service->decrypt('string', $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->service->encrypt('string', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
