<?php

namespace Ambta\DoctrineEncryptBundle\Service;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Encrypt service aware interface.
 */
interface EncryptServiceAwareInterface
{
    /**
     * Set the doctrine entity manager.
     */
    public function setEntityManager(?EntityManagerInterface $entityManager = null);

    /**
     * Change the encryptor.
     */
    public function setEncryptor(?EncryptorInterface $encryptor = null);

    /**
     * Get the current encryptor.
     *
     * @return EncryptorInterface|null returns the encryptor class or null
     */
    public function getEncryptor(): ?EncryptorInterface;

    /**
     * Used for the decrypt command so that the values in the database can actually be decrypted.
     */
    public function skipEncryption();

    /**
     * Used for the decrypt command so that the values in the database can actually be decrypted.
     */
    public function skipEncryptionOnTypes();

    /**
     * Process encrypt.
     *
     * @param 'string'|'datetime'|'json'|'array' $type
     */
    public function encrypt(string $type, $value);

    /**
     * Process decrypt.
     *
     * @param 'string'|'datetime'|'json'|'array' $type
     */
    public function decrypt(string $type, $value);
}
