<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional\BasicQueryTest;

use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

class BasicQueryDefuseTest extends AbstractBasicQueryTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new DefuseEncryptor(file_get_contents(__DIR__.'/../../fixtures/defuse.key'));
    }
}
