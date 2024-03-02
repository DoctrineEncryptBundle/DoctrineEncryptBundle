<?php


namespace DoctrineEncryptBundle\Tests\Functional;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use DoctrineEncryptBundle\Service\Encrypt;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use DoctrineEncryptBundle\DoctrineEncryptBundle;
use DoctrineEncryptBundle\Tests\DoctrineCompatibilityTrait;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\TestCase;

abstract class AbstractFunctionalTestCase extends TestCase
{
    use DoctrineCompatibilityTrait;
    
    /**
     *  @var Encrypt 
     */
    protected $service;

    /** 
     * @var EncryptorInterface 
     */
    protected $encryptor;

    /** 
     * @var false|string
     */
    protected $dbFile;

    /** 
     * @var EntityManager
     */
    protected $entityManager;

    abstract protected function getEncryptor(): EncryptorInterface;
    
    public function setUp(): void
    {
        $this->encryptor = $this->getEncryptor();
        $this->service = new Encrypt($this->encryptor);

        foreach (DoctrineEncryptBundle::ENCRYPT_TYPES as $encyptName => $encryptClass)
        {
            if (! Type::hasType($encyptName))
            {
                Type::addType($encyptName, $encryptClass);
            }
            /** @var \DoctrineEncryptBundle\Traits\DoctrineEncrypt $addedType */
            $addedType = Type::getType($encyptName);
            $addedType->setService($this->service);
        }

        // Create a simple "default" Doctrine ORM configuration
        $isDevMode = true;
        $proxyDir = null;
        $cache = null;

        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $useSimpleAnnotationReader = false;
            $config = Setup::createAnnotationMetadataConfiguration(
                array(__DIR__.'/fixtures/Entity'),
                $isDevMode,
                $proxyDir,
                $cache,
                $useSimpleAnnotationReader
            );
        }
        else
        {
            $reportFieldsWhereDeclared = true;
            $config = ORMSetup::createAttributeMetadataConfiguration(
                array(__DIR__.'/fixtures/Entity'),
                $isDevMode,
                $proxyDir,
                $cache,
                $reportFieldsWhereDeclared
            );
            $config->setLazyGhostObjectEnabled (true);
            $config->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        }

        // database configuration parameters
        $this->dbFile = tempnam(sys_get_temp_dir(), 'amb_db');
        $connOptions = array(
            'driver' => 'pdo_sqlite',
            'path'   => $this->dbFile,
        );

        // obtaining the entity manager
        $conn = DriverManager::getConnection($connOptions, $config);
        $dbalConf = $conn->getConfiguration();
        $this->entityManager = new EntityManager($conn, $dbalConf);

        $schemaTool = new SchemaTool($this->entityManager);
        $classes    = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        error_reporting(E_ALL);
    }

    public function tearDown(): void
    {
        $this->entityManager->getConnection()->close();
        unlink($this->dbFile);
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param string $stringn
     * @param string $string
     * @param string $message
     */
    public function assertStringDoesNotContain($needle, $string, $ignoreCase = false, $message = ''): void
    {
        $this->assertIsString($needle,$message);
        $this->assertIsString($string,$message);
        $this->assertIsBool($ignoreCase,$message);

        $constraint = new LogicalNot(new StringContains(
            $needle,
            $ignoreCase
        ));

        static::assertThat($string, $constraint, $message);
    }
}
