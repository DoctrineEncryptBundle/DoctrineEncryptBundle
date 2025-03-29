<?php

declare(strict_types=1);

namespace Ambta\DoctrineEncryptBundle\Encryptors;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Exception\UnableToDecryptException;
use Ambta\DoctrineEncryptBundle\Exception\UnableToEncryptException;

/**
 * Class for encrypting and decrypting with the defuse library.
 *
 * @author Michael de Groot <specamps@gmail.com>
 */
class DefuseEncryptor implements EncryptorInterface
{
    /** @var string */
    private $secret;

    /**
     * @param string $secret Secret used to encrypt/decrypt
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @throws UnableToEncryptException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function encrypt(string $data): string
    {
        try {
            return \Defuse\Crypto\Crypto::encryptWithPassword($data, $this->secret);
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::wrapExceptions()) {
                throw new UnableToEncryptException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    /**
     * @throws UnableToDecryptException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function decrypt(string $data): string
    {
        try {
            return \Defuse\Crypto\Crypto::decryptWithPassword($data, $this->secret);
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::wrapExceptions()) {
                throw new UnableToDecryptException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }
}
