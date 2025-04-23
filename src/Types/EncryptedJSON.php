<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\EncryptServiceAwareTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class EncryptedJSON extends TextType
{
    use EncryptServiceAwareTrait;

    public const TYPE = 'encrypted_json';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->decrypt('json', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->encrypt('json', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
