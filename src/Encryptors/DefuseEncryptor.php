<?php

namespace DoctrineEncryptBundle\Encryptors;

/**
 * Class for encrypting and decrypting with the defuse library
 */

class DefuseEncryptor implements EncryptorInterface
{
    /** @var string  */
    private $secret;

    /**
     * @param string $secret Secret used to encrypt/decrypt
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt(string $data): string
    {
        return \Defuse\Crypto\Crypto::encryptWithPassword($data, $this->secret);
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt(string $data): string
    {
        return \Defuse\Crypto\Crypto::decryptWithPassword($data, $this->secret);
    }
}
