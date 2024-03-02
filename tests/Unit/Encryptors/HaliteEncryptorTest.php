<?php

namespace DoctrineEncryptBundle\Tests\Unit\Encryptors;

use DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use PHPUnit\Framework\TestCase;

class HaliteEncryptorTest extends TestCase
{
    private const DATA = 'foobar';

    public function testEncryptExtension(): void
    {
        if (! extension_loaded('sodium') && !class_exists('ParagonIE_Sodium_Compat')) {
            $this->markTestSkipped('This test only runs when the sodium extension is enabled.');
        }
        $keyfile = __DIR__.'/fixtures/halite.key';
        $key = file_get_contents($keyfile);
        $halite = new HaliteEncryptor($key);

        $encrypted = $halite->encrypt(self::DATA);
        $this->assertNotSame(self::DATA, $encrypted);
        $decrypted = $halite->decrypt($encrypted);

        $this->assertSame(self::DATA, $decrypted);;
    }
}
