<?php

namespace DoctrineEncryptBundle\Tests\Unit\Service;

use DateTime;
use DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use DoctrineEncryptBundle\Service\Encrypt;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineEncryptServiceTest extends TestCase
{
    /**
     * @var Encrypt
     */
    private $service;

    protected function createMock($originalClassName): MockObject
    {
        $oldErrorLevel = ini_get('error_reporting');
        ini_set('error_reporting',E_ALL ^ E_DEPRECATED);

        $return = parent::createMock($originalClassName);

        ini_set('error_reporting',$oldErrorLevel);

        return $return;
    }


    protected function setUp(): void
    {
        /** @var EncryptorInterface|MockObject $encryptor */
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

        $this->service = new Encrypt($encryptor);
    }

    public function testEncrypt(): void
    {
        $string = 'Test';
        $result = $this->service->encrypt ('string', $string);

        $this->assertEquals('encrypted-'.$string.Encrypt::ENCRYPTION_MARKER, $result);
    }

    public function testDecrypt(): void
    {
        $string = 'encrypted-Test'.Encrypt::ENCRYPTION_MARKER;
        $result = $this->service->decrypt ('string', $string);

        $this->assertEquals('Test', $result);
    }

    public function testEncryptDateTime(): void
    {
        $datetime = new DateTime();
        $result = $this->service->encrypt ('datetime', $datetime);

        $this->assertEquals('encrypted-'.$datetime->format('Y-m-d H:i:s').Encrypt::ENCRYPTION_MARKER, $result);
    }

    public function testDecryptDateTime(): void
    {
        $datetime = new DateTime();
        $encrypted = $this->service->encrypt ('datetime', $datetime);

        $result = $this->service->decrypt ('datetime', $encrypted);

        $this->assertEquals($datetime->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
    }

    public function testEncryptJSON(): void
    {
        $json = '{"test":"value"}';
        $jsonData = json_decode($json, true);
        $result = $this->service->encrypt ('json', $jsonData);

        $this->assertEquals('encrypted-'.$json.Encrypt::ENCRYPTION_MARKER, $result);
    }

    public function testDecryptJSON(): void
    {
        $json = '{"test":"value"}';
        $jsonData = json_decode($json, true);
        $encrypted = $this->service->encrypt ('json', $jsonData);

        $result = $this->service->decrypt ('json', $encrypted);

        $this->assertEquals($jsonData, $result);
    }

    public function testEncryptArray(): void
    {
        $array = ['test', 'value'];
        $result = $this->service->encrypt ('simple_array', $array);

        $this->assertEquals('encrypted-'.implode(',',$array).Encrypt::ENCRYPTION_MARKER, $result);
    }

    public function testDecryptArray(): void
    {
        $array = ['test', 'value'];
        $encrypted = $this->service->encrypt ('simple_array', $array);

        $result = $this->service->decrypt ('simple_array', $encrypted);

        $this->assertEquals($array, $result);
    }
}
