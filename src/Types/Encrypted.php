<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\EncryptServiceAwareTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class Encrypted extends StringType
{
    use EncryptServiceAwareTrait;

    public const TYPE = 'encrypted';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->decrypt('string', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->encrypt('string', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
