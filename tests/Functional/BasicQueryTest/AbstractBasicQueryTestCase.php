<?php

namespace DoctrineEncryptBundle\Tests\Functional\BasicQueryTest;

use DateTime;
use DoctrineEncryptBundle\Service\Encrypt;
use DoctrineEncryptBundle\Tests\Functional\AbstractFunctionalTestCase;
use DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\CascadeTarget;
use DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\CascadeTargetArray;
use DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\CascadeTargetDateTime;
use DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\CascadeTargetJSON;
use DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\CascadeTargetStrtoupper;
use DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\VehicleCar;

abstract class AbstractBasicQueryTestCase extends AbstractFunctionalTestCase
{
    public function testPersistEntity(): void
    {
        $user = new CascadeTarget();
        $user->setNotSecret('My public information');
        $user->setSecret('top secret information');
        $this->entityManager->persist($user);
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(2, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($user)));
        $this->entityManager->flush();

        // Start transaction; insert; commit
        $this->assertEquals('top secret information',$user->getSecret());
        
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

        $user = new CascadeTarget();
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
        $this->assertEquals('top secret information',$user->getSecret());
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
        $this->assertEquals('top secret information change',$user->getSecret());
    }

    public function testStoredDataIsEncrypted(): void
    {
        $user = new CascadeTarget();
        $user->setNotSecret('My public information');
        $user->setSecret('my secret');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTarget WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];

        $this->assertStringEndsWith(Encrypt::ENCRYPTION_MARKER,$passwordData);
        $this->assertStringDoesNotContain('my secret',$passwordData);
        $this->assertEquals('my secret',$user->getSecret());

        $user->setSecret('my secret has changed');
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTarget WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];

        $this->assertStringEndsWith(Encrypt::ENCRYPTION_MARKER,$passwordData);
        $this->assertStringDoesNotContain('my secret has changed',$passwordData);
        $this->assertEquals('my secret has changed',$user->getSecret());
    }

    public function testNoUpdateForUnalteredChildrenOfAbstractEntities()
    {
        $car = new VehicleCar();
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

    public function testEntitySetterUseStrtoupper()
    {
        $user = new CascadeTargetStrtoupper();
        $user->setNotSecret('My public information');
        $user->setSecret('my secret');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetStrtoupper WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];
        $secret = $user->getSecret();

        $this->assertStringEndsWith(Encrypt::ENCRYPTION_MARKER,$passwordData);
        $this->assertStringDoesNotContain('my secret',$passwordData);
        $this->assertStringDoesNotContain('MY SECRET',$passwordData);
        $this->assertEquals('MY SECRET', $secret);
    }

    public function testEntityDateTime()
    {
        $datetime = new DateTime();
        $user = new CascadeTargetDateTime();
        $user->setNotSecret('My public information');
        $user->setSecret($datetime);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetDateTime WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];
        $secret = $user->getSecret();

        $this->assertStringEndsWith(Encrypt::ENCRYPTION_MARKER,$passwordData);
        $this->assertEquals($datetime->format('Y-m-d H:i:s'), $secret->format('Y-m-d H:i:s'));
    }

    public function testEntityJSON()
    {
        $json = ['test' => 'value'];
        $user = new CascadeTargetJSON();
        $user->setNotSecret('My public information');
        $user->setSecret($json);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetJSON WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];
        $secret = $user->getSecret();

        $this->assertStringEndsWith(Encrypt::ENCRYPTION_MARKER,$passwordData);
        $this->assertEquals($json, $secret);
    }

    public function testEntityArray()
    {
        $array = ['test', 'value'];
        $user = new CascadeTargetArray();
        $user->setNotSecret('My public information');
        $user->setSecret($array);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $connection = $this->entityManager->getConnection();
        $stmt       = $connection->prepare('SELECT * from CascadeTargetArray WHERE id = ?');
        $stmt->bindValue(1, $user->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $passwordData = $results[0]['secret'];
        $secret = $user->getSecret();

        $this->assertStringEndsWith(Encrypt::ENCRYPTION_MARKER,$passwordData);
        $this->assertEquals($array, $secret);
    }
}
