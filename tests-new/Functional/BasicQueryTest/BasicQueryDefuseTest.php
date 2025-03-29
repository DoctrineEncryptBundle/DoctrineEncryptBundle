<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\BasicQueryTest;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class BasicQueryDefuseTest extends AbstractBasicQueryTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new DefuseEncryptor(file_get_contents(__DIR__.'/../fixtures/defuse.key'));
    }
}
