<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToDecryptException;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToEncryptException;

/**
 * Encryptor interface for encryptors.
 *
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
interface EncryptorInterface extends \Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface
{
    /**
     * @param string $data Plain text to encrypt
     *
     * @return string Encrypted text
     *
     * @throws UnableToEncryptException
     * @throws \Throwable
     */
    public function encrypt(string $data): string;

    /**
     * @param string $data Encrypted text
     *
     * @return string Plain text
     *
     * @throws UnableToDecryptException
     * @throws \Throwable
     */
    public function decrypt(string $data): string;
}
