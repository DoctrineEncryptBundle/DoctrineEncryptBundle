<?php

namespace Ambta\DoctrineEncryptBundle\Factories;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class EncryptorFactory
{
    public static function createEncryptor(
        string $secret,
        string $encryptorClass,
    ): EncryptorInterface {
        $encryptor = new $encryptorClass($secret);

        return $encryptor;
    }
}
