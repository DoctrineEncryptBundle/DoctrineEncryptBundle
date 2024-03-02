<?php

namespace DoctrineEncryptBundle\Tests\Functional\DoctrineEncryptService;

use DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class DoctrineEncryptServiceDefuseTest extends AbstractDoctrineEncryptServiceTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new DefuseEncryptor(file_get_contents(__DIR__ . '/../fixtures/defuse.key'));
    }
}
