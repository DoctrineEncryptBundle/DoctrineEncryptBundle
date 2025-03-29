<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Unit\Encryptors;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\HaliteEncryptor;
use Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException;
use PHPUnit\Framework\TestCase;

class HaliteEncryptorTest extends TestCase
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

    public function testEncryptExtension(): void
    {
        if (!extension_loaded('sodium') && !class_exists('ParagonIE_Sodium_Compat')) {
            static::markTestSkipped('This test only runs when the sodium extension is enabled.');
        }
        $keyfile = __DIR__.'/fixtures/halite.key';
        $key     = file_get_contents($keyfile);
        $halite  = new HaliteEncryptor($key);

        $encrypted = $halite->encrypt(self::DATA);
        $this->assertNotSame(self::DATA, $encrypted);
        $decrypted = $halite->decrypt($encrypted);

        static::assertSame(self::DATA, $decrypted);
    }

    public function testEncryptorThrowsOwnExceptionWhenExceptionsAreNotWrapped(): void
    {
        DoctrineEncryptExtension::$wrapExceptions = false;

        try {
            (new HaliteEncryptor('not-a-valid-key'))->encrypt('foo');

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
            (new HaliteEncryptor('not-a-valid-key'))->encrypt('foo');

            $this->fail('The encryptor should have thrown an error');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(DoctrineEncryptBundleException::class, $e);
        }
    }
}
