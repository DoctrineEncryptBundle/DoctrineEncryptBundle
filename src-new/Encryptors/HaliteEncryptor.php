<?php

declare(strict_types=1);

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors;

use DoctrineEncryptBundle\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToDecryptException;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToEncryptException;

/**
 * Class for encrypting and decrypting with the halite library.
 *
 * @author Michael de Groot <specamps@gmail.com>
 */
final class HaliteEncryptor extends \Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor implements EncryptorInterface
{
    /**
     * @throws UnableToEncryptException
     * @throws \ParagonIE\Halite\Alerts\HaliteAlert
     * @throws \SodiumException
     * @throws \Throwable
     */
    public function encrypt(string $data): string
    {
        try {
            return parent::encrypt($data);
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::wrapExceptions()) {
                throw new UnableToEncryptException($e->getMessage(), $e->getCode(), $e->getPrevious());
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
            return parent::decrypt($data);
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::wrapExceptions()) {
                throw new UnableToDecryptException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
            throw $e;
        }
    }
}
