<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\DoctrineEncryptSubscriber;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class DoctrineEncryptSubscriberDefuseTest extends AbstractDoctrineEncryptSubscriberTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new DefuseEncryptor(file_get_contents(__DIR__.'/../fixtures/defuse.key'));
    }
}
