<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional\DoctrineEncryptService;

use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class DoctrineEncryptServiceDefuseTest extends AbstractDoctrineEncryptServiceTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new DefuseEncryptor(file_get_contents(__DIR__.'/../../fixtures/defuse.key'));
    }
}
