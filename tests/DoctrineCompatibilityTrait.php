<?php

namespace Ambta\DoctrineEncryptBundle\Tests;

use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeAnnotationReader;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeReader;
use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

trait DoctrineCompatibilityTrait
{
    /** @var DoctrineEncryptSubscriber */
    protected $subscriber;
    /** @var EncryptService */
    protected $encryptService;
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

    abstract protected function getEncryptor(): EncryptorInterface|MockObject;

    protected function setUp(): void
    {
        if (\Composer\InstalledVersions::satisfies(new \Composer\Semver\VersionParser(), 'symfony/doctrine-bridge', '>=6.4')) {
            $this->setUpMain();
        } else {
            $this->setUpLowest();
        }

        $this->resetQueryStack();
    }

    protected function setUpLowest(): void
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

        $this->encryptor      = $this->getEncryptor();
        $this->encryptService = new EncryptService();
        $this->encryptService->setEncryptor($this->encryptor);
        $this->encryptService->setEntityManager($this->entityManager);

        foreach (EncryptService::ENCRYPT_TYPES as $encyptName => $encryptClass) {
            if (!Type::hasType($encyptName)) {
                Type::addType($encyptName, $encryptClass);
            }
            $addedType = Type::getType($encyptName);
            $addedType->setEncryptService($this->encryptService);
            $addedType->setEntityManager($this->entityManager);
        }

        $schemaTool = new SchemaTool($this->entityManager);
        $classes    = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        $this->sqlLoggerStack = new DebugStack();
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger($this->sqlLoggerStack);

        $annotationCacheDirectory = __DIR__.'/cache';
        $this->createNewCacheDirectory($annotationCacheDirectory);
        $annotationReader = new AttributeAnnotationReader(new AttributeReader(), new AnnotationReader(), $annotationCacheDirectory);
        $this->subscriber = new DoctrineEncryptSubscriber($annotationReader, $this->encryptor);
        $this->entityManager->getEventManager()->addEventSubscriber($this->subscriber);

        error_reporting(E_ALL);
    }

    protected function setUpMain(): void
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

        $this->encryptor      = $this->getEncryptor();
        $this->encryptService = new EncryptService();
        $this->encryptService->setEncryptor($this->encryptor);
        $this->encryptService->setEntityManager($this->entityManager);

        foreach (EncryptService::ENCRYPT_TYPES as $encyptName => $encryptClass) {
            if (!Type::hasType($encyptName)) {
                Type::addType($encyptName, $encryptClass);
            }
            $addedType = Type::getType($encyptName);
            $addedType->setEncryptService($this->encryptService);
            $addedType->setEntityManager($this->entityManager);
        }

        $schemaTool = new SchemaTool($this->entityManager);
        $classes    = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        $annotationCacheDirectory = __DIR__.'/cache';
        $this->createNewCacheDirectory($annotationCacheDirectory);
        $this->subscriber = new DoctrineEncryptSubscriber(new AttributeReader(), $this->encryptor);
        $this->entityManager->getEventManager()->addEventSubscriber($this->subscriber);

        error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->close();
        unlink($this->dbFile);
    }

    protected function createNewCacheDirectory(string $annotationCacheDirectory): void
    {
        $this->recurseRmdir($annotationCacheDirectory);
        mkdir($annotationCacheDirectory);
    }

    protected function recurseRmdir($dir): bool
    {
        $contents = scandir($dir);
        if (is_array($contents)) {
            $files = array_diff($contents, ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file") && !is_link("$dir/$file")) ? $this->recurseRmdir("$dir/$file") : unlink("$dir/$file");
            }

            return rmdir($dir);
        }

        return false;
    }

    protected function resetQueryStack(): void
    {
        if (\Composer\InstalledVersions::satisfies(new \Composer\Semver\VersionParser(), 'symfony/doctrine-bridge', '<6.4')) {
            $this->sqlLoggerStack->queries = [];
        } else {
            $this->debugDataHolder->reset();
        }
    }

    /**
     * Execute statement and fetch all results.
     *
     * Helper-method since methods changed in different supported versions of Doctrine
     */
    protected function executeStatementFetchAll(\Doctrine\DBAL\Statement $statement)
    {
        if (method_exists($statement, 'executeQuery')) {
            return $statement->executeQuery()->fetchAllAssociative();
        } else {
            $statement->execute();

            return $statement->fetchAll();
        }
    }

    /**
     * Execute statement and fetch singe row.
     *
     * Helper-method since methods changed in different supported versions of Doctrine
     */
    protected function executeStatementFetch(\Doctrine\DBAL\Statement $statement)
    {
        if (method_exists($statement, 'executeQuery')) {
            return $statement->executeQuery()->fetchAssociative();
        } else {
            $statement->execute();

            return $statement->fetch();
        }
    }
}
