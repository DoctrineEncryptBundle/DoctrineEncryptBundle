<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\EncryptServiceAwareTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class EncryptedDateTime extends StringType
{
    use EncryptServiceAwareTrait;

    public const TYPE = 'encrypted_datetime';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->decrypt('datetime', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->encrypt('datetime', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
