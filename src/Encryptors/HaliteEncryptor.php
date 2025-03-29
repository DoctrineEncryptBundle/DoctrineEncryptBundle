<?php

declare(strict_types=1);

namespace Ambta\DoctrineEncryptBundle\Encryptors;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Exception\UnableToDecryptException;
use Ambta\DoctrineEncryptBundle\Exception\UnableToEncryptException;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class for encrypting and decrypting with the halite library.
 *
 * @author Michael de Groot <specamps@gmail.com>
 */
class HaliteEncryptor implements EncryptorInterface
{
    /** @var EncryptionKey|null */
    private $encryptionKey;
    /** @var string */
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @throws UnableToEncryptException
     * @throws \ParagonIE\Halite\Alerts\HaliteAlert
     * @throws \SodiumException
     * @throws \Throwable
     */
    public function encrypt(string $data): string
    {
        try {
            return Crypto::encrypt(new HiddenString($data), $this->getKey());
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::wrapExceptions()) {
                throw new UnableToEncryptException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    /**
     * @throws UnableToDecryptException
     * @throws \ParagonIE\Halite\Alerts\HaliteAlert
     * @throws \SodiumException
     * @throws \Throwable
     */
    public function decrypt(string $data): string
    {
        try {
            return Crypto::decrypt($data, $this->getKey())->getString();
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::wrapExceptions()) {
                throw new UnableToDecryptException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    private function getKey(): EncryptionKey
    {
        if ($this->encryptionKey === null) {
            $this->encryptionKey = KeyFactory::importEncryptionKey(new HiddenString($this->secret));
        }

        return $this->encryptionKey;
    }
}
