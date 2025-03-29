<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeReader;
use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractFunctionalTestCase extends TestCase
{
    /** @var DoctrineEncryptSubscriber */
    protected $subscriber;
    /** @var EncryptorInterface */
    protected $encryptor;
    /** @var false|string */
    protected $dbFile;
    /** @var EntityManager */
    protected $entityManager;
    /** @var DebugStack */
    protected $sqlLoggerStack;
    /** @var DebugDataHolder */
    protected $debugDataHolder;

    /** @var Filesystem */
    private $filesystem;

    abstract protected function getEncryptor(): EncryptorInterface;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();

        if (PHP_VERSION_ID < 80000) {
            $this->setUpPHP7();
        } else {
            $this->setUpPHP8();
        }

        $this->resetQueryStack();
    }

    public function setUpPHP7(): void
    {
        // Create a simple "default" Doctrine ORM configuration for Annotations
        $isDevMode                 = true;
        $proxyDir                  = null;
        $cache                     = null;
        $useSimpleAnnotationReader = false;

        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__.'/fixtures/Entity'],
            $isDevMode,
            $proxyDir,
            $cache,
            $useSimpleAnnotationReader
        );

        // database configuration parameters
        $this->dbFile = tempnam(sys_get_temp_dir(), 'amb_db');
        $conn         = [
            'driver' => 'pdo_sqlite',
            'path'   => $this->dbFile,
        ];

        // obtaining the entity manager
        $this->entityManager = EntityManager::create($conn, $config);

        // Using savepoints will be default in dbal 4.0, so use it in 3.0 as well
        $this->entityManager->getConnection()->setNestTransactionsWithSavepoints(true);

        $schemaTool = new SchemaTool($this->entityManager);
        $classes    = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        $this->sqlLoggerStack = new DebugStack();
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger($this->sqlLoggerStack);

        $this->encryptor          = $this->getEncryptor();
        $annotationCacheDirectory = sys_get_temp_dir().'/cache';
        $this->createNewCacheDirectory($annotationCacheDirectory);
        $annotationReader = new AttributeAnnotationReader(new AttributeReader(), new AnnotationReader(), $annotationCacheDirectory);
        $this->subscriber = new DoctrineEncryptSubscriber($annotationReader, $this->encryptor);
        $this->entityManager->getEventManager()->addEventSubscriber($this->subscriber);
    }

    public function setUpPHP8(): void
    {
        // Create a simple "default" Doctrine ORM configuration for Annotations
        $isDevMode = true;
        $proxyDir  = null;
        $cache     = null;

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__.'/fixtures/Entity'],
            $isDevMode,
            $proxyDir,
            $cache
        );

        $this->debugDataHolder = new DebugDataHolder();

        $debugMiddleware = new DebugMiddleware($this->debugDataHolder, null);
        $config->setMiddlewares([$debugMiddleware]);

        // database configuration parameters
        $this->dbFile = tempnam(sys_get_temp_dir(), 'amb_db');
        $conn         = [
            'driver' => 'pdo_sqlite',
            'path'   => $this->dbFile,
        ];

        // obtaining the entity manager
        $this->entityManager = new EntityManager(DriverManager::getConnection($conn, $config), $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $classes    = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        $this->encryptor          = $this->getEncryptor();
        $annotationCacheDirectory = sys_get_temp_dir().'/cache';
        $this->createNewCacheDirectory($annotationCacheDirectory);
        $this->subscriber = new DoctrineEncryptSubscriber(new AttributeReader(), $this->encryptor);
        $this->entityManager->getEventManager()->addEventSubscriber($this->subscriber);
    }

    public function tearDown(): void
    {
        $this->entityManager->getConnection()->close();
        $this->filesystem->remove($this->dbFile);
    }

    protected function createNewCacheDirectory(string $annotationCacheDirectory): void
    {
        $this->recurseRmdir($annotationCacheDirectory);
        $this->filesystem->mkdir($annotationCacheDirectory);
    }

    protected function recurseRmdir($dir): void
    {
        $this->filesystem->remove($dir);
    }

    /**
     * Get all queries.
     */
    protected function getAllDebugQueries(): array
    {
        if (PHP_VERSION_ID < 80000) {
            return $this->sqlLoggerStack->queries;
        }

        $data = $this->debugDataHolder->getData();

        return isset($data['default']) ? $data['default'] : [];
    }

    /**
     * Get all queries, except ones containing the word 'SAVEPOINT'.
     *
     * The use of savepoints changes between different versions of doctrine/dbal, so let's ignore those.
     */
    protected function getDebugQueries(): array
    {
        return array_filter(
            $this->getAllDebugQueries(),
            static function ($queryData) {
                return stripos($queryData['sql'], 'SAVEPOINT') === false;
            }
        );
    }

    protected function getLatestInsertQuery(): ?array
    {
        $insertQueries = array_values(array_filter($this->getDebugQueries(), static function ($queryData) {
            return stripos($queryData['sql'], 'INSERT ') === 0;
        }));

        return current(array_reverse($insertQueries)) ?: null;
    }

    protected function getLatestUpdateQuery(): ?array
    {
        $updateQueries = array_values(array_filter($this->getDebugQueries(), static function ($queryData) {
            return stripos($queryData['sql'], 'UPDATE ') === 0;
        }));

        return current(array_reverse($updateQueries)) ?: null;
    }

    /**
     * Using the SQL Logger Stack this method retrieves the current query count executed in this test.
     */
    protected function getCurrentQueryCount(): int
    {
        return count($this->getDebugQueries());
    }

    protected function resetQueryStack(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->sqlLoggerStack->queries = [];
        } else {
            $this->debugDataHolder->reset();
        }
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param string $string
     * @param string $message
     */
    public function assertStringDoesNotContain($needle, $string, $ignoreCase = false, $message = ''): void
    {
        static::assertIsString($needle, $message);
        static::assertIsString($string, $message);
        static::assertIsBool($ignoreCase, $message);

        $constraint = new LogicalNot(new StringContains(
            $needle,
            $ignoreCase
        ));

        static::assertThat($string, $constraint, $message);
    }
}
