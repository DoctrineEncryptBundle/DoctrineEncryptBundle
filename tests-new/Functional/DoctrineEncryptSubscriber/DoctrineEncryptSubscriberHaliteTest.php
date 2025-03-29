<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\DoctrineEncryptSubscriber;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;

class DoctrineEncryptSubscriberHaliteTest extends AbstractDoctrineEncryptSubscriberTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new HaliteEncryptor(file_get_contents(__DIR__.'/../fixtures/halite.key'));
    }

    public function setUp(): void
    {
        if (!extension_loaded('sodium') && !class_exists('ParagonIE_Sodium_Compat')) {
            $this->markTestSkipped('This test only runs when the sodium extension is enabled.');

            return;
        }

        parent::setUp();
    }
}
