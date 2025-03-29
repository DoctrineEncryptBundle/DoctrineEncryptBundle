<?php

namespace Ambta\DoctrineEncryptBundle\Service;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine event service which encrypt/decrypt entities.
 */
class Encrypt
{
    /**
     * Appended to end of encrypted value.
     */
    public const ENCRYPTION_MARKER = '<ENC>';

    /**
     * Encryptor.
     *
     * @var EncryptorInterface|null
     */
    private $encryptor;

    /**
     * Doctrine AbstractPlatform. Always uses MySQL80Platform to ensure that the values are encrypted and decrypted the same even if moved between databases.
     *
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * Initialization of service.
     *
     * @param EncryptorInterface $encryptor (Optional)  An EncryptorInterface
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
        $this->platform  = new MySQL80Platform();
    }

    /**
     * Change the encryptor.
     */
    public function setEncryptor(?EncryptorInterface $encryptor = null)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * Get the current encryptor.
     *
     * @return EncryptorInterface|null returns the encryptor class or null
     */
    public function getEncryptor(): ?EncryptorInterface
    {
        return $this->encryptor;
    }

    /**
     * Process encrypt.
     *
     * @param 'string'|'datetime'|'json'|'array' $type
     */
    public function encrypt(string $type, $value)
    {
        if (is_null($value)) {
            return null;
        }

        $encryptDbalType = Type::getType($type);
        $usedValue       = $encryptDbalType->convertToDatabaseValue($value, $this->platform);
        if (array_key_exists('DOCTRINE_SKIP_ENCRYPT', $_ENV) && $_ENV['DOCTRINE_SKIP_ENCRYPT'] === true) {
            return $usedValue;
        }

        if (substr($usedValue, -strlen(self::ENCRYPTION_MARKER)) != self::ENCRYPTION_MARKER) {
            return $this->getEncryptor()->encrypt($usedValue).self::ENCRYPTION_MARKER;
        }

        return $usedValue;
    }

    /**
     * Process decrypt.
     *
     * @param 'string'|'datetime'|'json'|'array' $type
     */
    public function decrypt(string $type, $value)
    {
        if (is_null($value)) {
            return null;
        }

        $encryptDbalType = Type::getType($type);
        if (substr($value, -strlen(self::ENCRYPTION_MARKER)) == self::ENCRYPTION_MARKER) {
            $currentValue = $this->getEncryptor()->decrypt(substr($value, 0, -strlen(self::ENCRYPTION_MARKER)));

            return $encryptDbalType->convertToPHPValue($currentValue, $this->platform);
        }

        return $encryptDbalType->convertToPHPValue($value, $this->platform);
    }
}
