<?php

namespace DoctrineEncryptBundle\Tests\Functional\BasicQueryTest;

use DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class BasicQueryDefuseTest extends AbstractBasicQueryTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new DefuseEncryptor(file_get_contents(__DIR__ . '/../fixtures/defuse.key'));
    }
}
