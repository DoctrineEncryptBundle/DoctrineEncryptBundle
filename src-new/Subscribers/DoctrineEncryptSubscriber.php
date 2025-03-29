<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Subscribers;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException as parentBundleException;
use Ambta\DoctrineEncryptBundle\Exception\UnableToEncryptException as UnableToEncryptParentBundleException;
use Ambta\DoctrineEncryptBundle\Exception\UnableToDecryptException as UnableToDecryptParentBundleException;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToDecryptException;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToEncryptException;

/**
 * Doctrine event subscriber which encrypt/decrypt entities.
 */
final class DoctrineEncryptSubscriber extends \Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber
{
    public function __construct($annReader, EncryptorInterface $encryptor)
    {
        parent::__construct($annReader, $encryptor);

        $this->annotationName = Encrypted::class;
    }

    /**
     * Process (encrypt/decrypt) entities fields.
     *
     * @param object $entity             doctrine entity
     * @param bool   $isEncryptOperation If true - encrypt, false - decrypt entity
     *
     * @throws \RuntimeException|DoctrineEncryptBundleException
     */
    public function processFields(object $entity, EntityManagerInterface $entityManager, bool $isEncryptOperation = true): ?object
    {
        try {
            return parent::processFields($entity, $entityManager, $isEncryptOperation);
        } catch (UnableToEncryptParentBundleException $e) {
            throw new UnableToEncryptException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (UnableToDecryptParentBundleException $e) {
            throw new UnableToDecryptException($e->getMessage(), $e->getCode(), $e->getPrevious());
        } catch (parentBundleException $e) {
            throw new DoctrineEncryptBundleException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
