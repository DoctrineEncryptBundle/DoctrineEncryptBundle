<?php

namespace Ambta\DoctrineEncryptBundle\Factories;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use ParagonIE\Halite\KeyFactory;
use Symfony\Component\Filesystem\Filesystem;

class SecretFactory
{
    /**
     * @var string
     */
    private $secretDirectory;
    /**
     * @var bool
     */
    private $enableSecretCreation;

    public function __construct(string $secretDirectory, bool $enableSecretCreation)
    {
        $this->secretDirectory      = $secretDirectory;
        $this->enableSecretCreation = $enableSecretCreation;
    }

    /**
     * @param string $className Which class to get a secret for
     *
     * @phpstan-assert value-of<DoctrineEncryptExtension::SupportedEncryptorClasses> $className
     *
     * @return string
     */
    public function getSecret(string $className)
    {
        if (!in_array($className, DoctrineEncryptExtension::SupportedEncryptorClasses)) {
            throw new \RuntimeException(sprintf('Class "%s" is not supported by %s', $className, self::class));
        }

        if ($className === HaliteEncryptor::class) {
            $filename = '.Halite.key';
        } else {
            $filename = '.Defuse.key';
        }

        $secretPath = $this->secretDirectory.DIRECTORY_SEPARATOR.$filename;

        if (!file_exists($secretPath)) {
            try {
                if (!$this->enableSecretCreation) {
                    throw new \RuntimeException('Creation of secrets is not enabled');
                }

                return $this->createSecret($secretPath, $className);
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf('DoctrineEncryptBundle: Unable to create secret "%s"', $secretPath), $e->getCode(), $e);
            }
        }

        if (!is_readable($secretPath) || ($secret = file_get_contents($secretPath)) === false) {
            throw new \RuntimeException(sprintf('DoctrineEncryptBundle: Unable to read secret "%s"', $secretPath));
        }

        return $secret;
    }

    /**
     * Generate a new secret and store it on the filesystem.
     *
     * @param string $secretPath Where to store the secret
     * @param string $className  Which type of secret to generate
     *
     * @phpstan-param value-of<DoctrineEncryptExtension::SupportedEncryptorClasses> $className
     *
     * @return string The generated secret
     */
    private function createSecret(string $secretPath, string $className)
    {
        if ($className === HaliteEncryptor::class) {
            $encryptionKey = KeyFactory::generateEncryptionKey();
            KeyFactory::save($encryptionKey, $secretPath);
            $secret = KeyFactory::export($encryptionKey)->getString();
        } elseif ($className === DefuseEncryptor::class) {
            $secret = bin2hex(random_bytes(255));
            file_put_contents($secretPath, $secret);
        }

        return $secret;
    }
}
