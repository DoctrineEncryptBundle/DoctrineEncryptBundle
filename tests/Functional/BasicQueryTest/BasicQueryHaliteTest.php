<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional\BasicQueryTest;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;

class BasicQueryHaliteTest extends AbstractBasicQueryTestCase
{
    protected function getEncryptor(): EncryptorInterface
    {
        return new HaliteEncryptor(file_get_contents(__DIR__.'/../fixtures/halite.key'));
    }

    public function setUp(): void
    {
        if (!extension_loaded('sodium') && !class_exists('ParagonIE_Sodium_Compat')) {
            static::markTestSkipped('This test only runs when the sodium extension is enabled.');

            return;
        }

        parent::setUp();
    }
}
