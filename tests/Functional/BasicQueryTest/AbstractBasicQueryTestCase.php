<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional\BasicQueryTest;

use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\CascadeTarget;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\CascadeTargetDateTime;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\CascadeTargetStrtoupperWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\CascadeTargetWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\VehicleCar;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\VehicleCarWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\Functional\AbstractFunctionalTestCase;

abstract class AbstractBasicQueryTestCase extends AbstractFunctionalTestCase
{
    public function testPersistEntity(): void
    {
        $user = new CascadeTarget();
        $user->setNotSecret('My public information');
        $user->setSecret('top secret information');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Start transaction; insert; commit
        static::assertEquals('top secret information', $user->getSecret());
        static::assertEquals(3, $this->getCurrentQueryCount());
    }

    public function testPersistEntityWithTypes(): void
    {
        $user = new CascadeTargetWithTypes();
        $user->setNotSecret('My public information');
        $user->setSecret('top secret information');
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(2, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();

        // Start transaction; insert; commit
        $this->assertEquals('top secret information', $user->getSecret());

        $user->setSecret('top secret information');
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));

        $user->setSecret('top secret info');
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(1, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();
    }

    public function testNoUpdateOnReadEncrypted(): void
    {
        $this->entityManager->beginTransaction();
        static::assertEquals(1, $this->getCurrentQueryCount());

        $user = new CascadeTarget();
        $user->setNotSecret('My public information');
        $user->setSecret('top secret information');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        static::assertEquals(2, $this->getCurrentQueryCount());

        // Test if no query is executed when doing nothing
        $this->entityManager->flush();
        static::assertEquals(2, $this->getCurrentQueryCount());

        // Test if no query is executed when reading unrelated field
        $user->getNotSecret();
        $this->entityManager->flush();
        static::assertEquals(2, $this->getCurrentQueryCount());

        // Test if no query is executed when reading related field and if field is valid
        static::assertEquals('top secret information', $user->getSecret());
        $this->entityManager->flush();
        static::assertEquals(2, $this->getCurrentQueryCount());

        // Test if 1 query is executed when updating entity
        $user->setSecret('top secret information change');
        $this->entityManager->flush();
        static::assertEquals(3, $this->getCurrentQueryCount());
        static::assertEquals('top secret information change', $user->getSecret());

        $this->entityManager->rollback();
        static::assertEquals(4, $this->getCurrentQueryCount());
    }

    public function testNoUpdateOnReadEncryptedWithType(): void
    {
        $this->entityManager->beginTransaction();

        $user = new CascadeTargetWithTypes();
        $user->setNotSecret('My public information');
        $user->setSecret('top secret information');
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(2, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();

        // Test if no query is executed when doing nothing
        $this->entityManager->flush();

        // Test if no query is executed when reading unrelated field
        $user->getNotSecret();
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();

        // Test if no query is executed when reading related field and if field is valid
        $this->assertEquals('top secret information', $user->getSecret());
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();

        // Test if 1 query is executed when updating entity
        $user->setSecret('top secret information change');
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(1, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();
        $this->assertEquals('top secret information change', $user->getSecret());
    }

    public function testStoredDataIsEncrypted(): void
    {
        $user = new CascadeTarget();
        $user->setNotSecret('My public information');
        $user->setSecret('my secret');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $queryData    = $this->getLatestInsertQuery();
        $params       = array_values($queryData['params']);
        $passwordData = $params[0] === 'My public information' ? $params[1] : $params[0];

        $this->assertStringEndsWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $passwordData);
        $this->assertStringDoesNotContain('my secret', $passwordData);

        $user->setSecret('my secret has changed');
        $this->entityManager->flush();

        $queryData    = $this->getLatestUpdateQuery();
        $passwordData = array_values($queryData['params'])[0];

        $this->assertStringEndsWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $passwordData);
        $this->assertStringDoesNotContain('my secret', $passwordData);
    }

    public function testNoUpdateForUnalteredChildrenOfAbstractEntities()
    {
        $car = new VehicleCar();
        $car->setSecret('top secret information');
        $car->setNotSecret('123-test');
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        // start transaction, insert, commit
        static::assertEquals(3, $this->getCurrentQueryCount());

        // Remove all logged queries
        $this->resetQueryStack();

        // Set NotSecret with same data - this does not modify the entity and should not trigger an update
        $car->setNotSecret('123-test');
        $this->entityManager->flush();

        // Verify there are no queries executed
        $this->assertNull($this->getLatestUpdateQuery());
        static::assertEquals(0, $this->getCurrentQueryCount());
    }

    public function testNoUpdateForUnalteredChildrenOfAbstractEntitiesWitTypes()
    {
        $car = new VehicleCarWithTypes();
        $car->setSecret('top secret information');
        $car->setNotSecret('123-test');
        $this->entityManager->persist($car);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(3, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($car)));
        $this->entityManager->flush();

        // Set NotSecret with same data - this does not modify the entity and should not trigger an update
        $car->setNotSecret('123-test');
        $this->entityManager->persist($car);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($car)));
        $this->entityManager->flush();
    }

    public function testStoredDataIsEncryptedWithTypes(): void
    {
        $user = new CascadeTargetWithTypes();
        $user->setNotSecret('My public information');
        $user->setSecret('my secret');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetWithTypes WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results      = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];

        $this->assertStringEndsWith(EncryptService::ENCRYPTION_MARKER, $passwordData);
        $this->assertStringDoesNotContain('my secret', $passwordData);
        $this->assertEquals('my secret', $user->getSecret());

        $user->setSecret('my secret has changed');
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetWithTypes WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results      = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];

        $this->assertStringEndsWith(EncryptService::ENCRYPTION_MARKER, $passwordData);
        $this->assertStringDoesNotContain('my secret has changed', $passwordData);
        $this->assertEquals('my secret has changed', $user->getSecret());
    }

    public function testEntitySetterUseStrtoupperWithTypes()
    {
        $user = new CascadeTargetStrtoupperWithTypes();
        $user->setNotSecret('My public information');
        $user->setSecret('my secret');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetStrtoupperWithTypes WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results      = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];
        $secret       = $user->getSecret();

        $this->assertStringEndsWith(EncryptService::ENCRYPTION_MARKER, $passwordData);
        $this->assertStringDoesNotContain('my secret', $passwordData);
        $this->assertStringDoesNotContain('MY SECRET', $passwordData);
        $this->assertEquals('MY SECRET', $secret);
    }

    public function testEntityDateTimeWithTypes()
    {
        $datetime = new \DateTime();
        $user     = new CascadeTargetDateTime();
        $user->setNotSecret('My public information');
        $user->setSecret($datetime);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetDateTime WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results      = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];
        $secret       = $user->getSecret();

        $this->assertStringEndsWith(EncryptService::ENCRYPTION_MARKER, $passwordData);
        $this->assertEquals($datetime->format('Y-m-d H:i:s'), $secret->format('Y-m-d H:i:s'));
    }
}
