<?php

namespace DoctrineEncryptBundle\Tests\Unit\Encryptors;

use DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use PHPUnit\Framework\TestCase;

class DefuseEncryptorTest extends TestCase
{
    private const DATA = 'foobar';

    public function testEncrypt(): void
    {
        $keyfile = __DIR__.'/fixtures/defuse.key';
        $key = file_get_contents($keyfile);
        $defuse = new DefuseEncryptor($keyfile);

        $encrypted = $defuse->encrypt(self::DATA);
        $this->assertNotSame(self::DATA, $encrypted);
        $decrypted = $defuse->decrypt($encrypted);

        $this->assertSame(self::DATA, $decrypted);
        $newkey = file_get_contents($keyfile);
        $this->assertSame($key, $newkey, 'The key must not be modified');
    }
}
