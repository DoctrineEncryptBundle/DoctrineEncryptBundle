<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Unit\Service;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Ambta\DoctrineEncryptBundle\Tests\DoctrineCompatibilityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineEncryptServiceTest extends TestCase
{
    use DoctrineCompatibilityTrait;

    protected function createMock($originalClassName): MockObject
    {
        $oldErrorLevel = ini_get('error_reporting');
        ini_set('error_reporting', E_ALL ^ E_DEPRECATED);

        $return = parent::createMock($originalClassName);

        ini_set('error_reporting', $oldErrorLevel);

        return $return;
    }

    protected function getEncryptor(): EncryptorInterface|MockObject
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor
            ->expects($this->any())
            ->method('encrypt')
            ->willReturnCallback(function (string $arg) {
                return 'encrypted-'.$arg;
            })
        ;
        $encryptor
            ->expects($this->any())
            ->method('decrypt')
            ->willReturnCallback(function (string $arg) {
                return preg_replace('/^encrypted-/', '', $arg);
            })
        ;

        return $encryptor;
    }

    public function testEncrypt(): void
    {
        $string = 'Test';
        $result = $this->encryptService->encrypt('string', $string);

        $this->assertEquals('encrypted-'.$string.EncryptService::ENCRYPTION_MARKER, $result);
    }

    public function testDecrypt(): void
    {
        $string = 'encrypted-Test'.EncryptService::ENCRYPTION_MARKER;
        $result = $this->encryptService->decrypt('string', $string);

        $this->assertEquals('Test', $result);
    }

    public function testEncryptDateTime(): void
    {
        $datetime = new \DateTime();
        $result   = $this->encryptService->encrypt('datetime', $datetime);

        $this->assertEquals('encrypted-'.$datetime->format('Y-m-d H:i:s').EncryptService::ENCRYPTION_MARKER, $result);
    }

    public function testDecryptDateTime(): void
    {
        $datetime  = new \DateTime();
        $encrypted = $this->encryptService->encrypt('datetime', $datetime);

        $result = $this->encryptService->decrypt('datetime', $encrypted);

        $this->assertEquals($datetime->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
    }

    public function testEncryptJSON(): void
    {
        $json     = '{"test":"value"}';
        $jsonData = json_decode($json, true);
        $result   = $this->encryptService->encrypt('json', $jsonData);

        $this->assertEquals('encrypted-'.$json.EncryptService::ENCRYPTION_MARKER, $result);
    }

    public function testDecryptJSON(): void
    {
        $json      = '{"test":"value"}';
        $jsonData  = json_decode($json, true);
        $encrypted = $this->encryptService->encrypt('json', $jsonData);

        $result = $this->encryptService->decrypt('json', $encrypted);

        $this->assertEquals($jsonData, $result);
    }

    public function testEncryptArray(): void
    {
        $array  = ['test', 'value'];
        $result = $this->encryptService->encrypt('simple_array', $array);

        $this->assertEquals('encrypted-'.implode(',', $array).EncryptService::ENCRYPTION_MARKER, $result);
    }

    public function testDecryptArray(): void
    {
        $array     = ['test', 'value'];
        $encrypted = $this->encryptService->encrypt('simple_array', $array);

        $result = $this->encryptService->decrypt('simple_array', $encrypted);

        $this->assertEquals($array, $result);
    }
}
