<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Unit\Encryptors;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\DefuseEncryptor;
use Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException;
use PHPUnit\Framework\TestCase;

class DefuseEncryptorTest extends TestCase
{
    private const DATA = 'foobar';
    /** @var bool */
    private $originalWrapExceptions;

    protected function setUp(): void
    {
        $this->originalWrapExceptions = DoctrineEncryptExtension::$wrapExceptions;
    }

    protected function tearDown(): void
    {
        DoctrineEncryptExtension::$wrapExceptions = $this->originalWrapExceptions;
    }

    public function testEncrypt(): void
    {
        $keyfile = __DIR__.'/fixtures/defuse.key';
        $key     = file_get_contents($keyfile);
        $defuse  = new DefuseEncryptor($keyfile);

        $encrypted = $defuse->encrypt(self::DATA);
        $this->assertNotSame(self::DATA, $encrypted);
        $decrypted = $defuse->decrypt($encrypted);

        static::assertSame(self::DATA, $decrypted);
        $newkey = file_get_contents($keyfile);
        static::assertSame($key, $newkey, 'The key must not be modified');
    }

    public function testEncryptorThrowsOwnExceptionWhenExceptionsAreNotWrapped(): void
    {
        DoctrineEncryptExtension::$wrapExceptions = false;

        try {
            (new DefuseEncryptor('not-a-valid-key'))->decrypt('foo');

            $this->fail('The encryptor should have thrown an error');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\PHPUnit\Framework\Exception::class, $e);
            $this->assertNotInstanceOf(DoctrineEncryptBundleException::class, $e);
        }
    }

    public function testEncryptorThrowsBundleExceptionWhenExceptionsAreWrapped(): void
    {
        DoctrineEncryptExtension::$wrapExceptions = true;

        try {
            (new DefuseEncryptor('not-a-valid-key'))->decrypt('foo');

            $this->fail('The encryptor should have thrown an error');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(DoctrineEncryptBundleException::class, $e);
        }
    }
}
