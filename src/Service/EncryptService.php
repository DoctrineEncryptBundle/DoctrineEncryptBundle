<?php

namespace Ambta\DoctrineEncryptBundle\Service;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Types\Encrypted;
use Ambta\DoctrineEncryptBundle\Types\EncryptedArray;
use Ambta\DoctrineEncryptBundle\Types\EncryptedDateTime;
use Ambta\DoctrineEncryptBundle\Types\EncryptedJSON;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Doctrine event service which encrypt/decrypt entities.
 */
final class EncryptService implements EncryptServiceAwareInterface
{
    /**
     * Appended to end of encrypted value.
     */
    public const ENCRYPTION_MARKER = '<ENC>';

    public const ENCRYPT_TYPES = [
        'encrypted'          => Encrypted::class,
        'encrypted_datetime' => EncryptedDateTime::class,
        'encrypted_json'     => EncryptedJSON::class,
        'encrypted_array'    => EncryptedArray::class
    ];

    /**
     * Encryptor.
     *
     * @var EncryptorInterface|null
     */
    private $encryptor;

    /**
     * @var EntityManagerInterface|null
     */
    private $entityManager;

    /**
     * @var bool
     */
    private $skipEncryption = false;

    public function __construct(
        ?EntityManagerInterface $entityManager = null,
        ?EncryptorInterface $encryptor = null
    ) {
        $this->entityManager = $entityManager;
        $this->encryptor     = $encryptor;
    }

    /**
     * @required
     *
     * Set the doctrine entity manager.
     */
    #[Required]
    public function setEntityManager(?EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @required
     *
     * Change the encryptor.
     */
    #[Required]
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
     * Used for the decrypt command so that the values in the database can actually be decrypted.
     */
    public function skipEncryption()
    {
        $this->skipEncryption = true;
    }

    /**
     * Used for the decrypt command so that the values in the database can actually be decrypted.
     */
    public function skipEncryptionOnTypes()
    {
        $this->skipEncryption();
        foreach (self::ENCRYPT_TYPES as $encyptName => $encryptClass) {
            if (Type::hasType($encyptName)) {
                $addedType = Type::getType($encyptName);
                $addedType->getEncryptService()->skipEncryption();
            }
        }
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
        $usedValue       = $encryptDbalType->convertToDatabaseValue($value, $this->entityManager->getConnection()->getDatabasePlatform());
        if ($this->skipEncryption === true) {
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

            return $encryptDbalType->convertToPHPValue($currentValue, $this->entityManager->getConnection()->getDatabasePlatform());
        }

        return $encryptDbalType->convertToPHPValue($value, $this->entityManager->getConnection()->getDatabasePlatform());
    }
}
