<?php

namespace Ambta\DoctrineEncryptBundle\Types;

use Ambta\DoctrineEncryptBundle\Traits\EncryptServiceAwareTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class EncryptedArray extends TextType
{
    use EncryptServiceAwareTrait;

    public const TYPE = 'encrypted_array';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->decrypt('simple_array', $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->getEncryptService()->encrypt('simple_array', $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
