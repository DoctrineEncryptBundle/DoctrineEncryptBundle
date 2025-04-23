<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional\DoctrineEncryptService;

use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\CascadeTargetWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\ClassTableInheritanceBaseWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\ClassTableInheritanceChildWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\OwnerWithTypes;
use Ambta\DoctrineEncryptBundle\Tests\Functional\AbstractFunctionalTestCase;

abstract class AbstractDoctrineEncryptServiceTestCase extends AbstractFunctionalTestCase
{
    public function testEncryptionHappensOnOnlyAnnotatedFields(): void
    {
        $secret    = "It's a secret";
        $notSecret = "You're all welcome to know this.";
        $em        = $this->entityManager;
        $owner     = new OwnerWithTypes();
        $owner->setSecret($secret);
        $owner->setNotSecret($notSecret);
        $em->persist($owner);
        $em->flush();
        $em->clear();
        unset($owner);

        $connection = $em->getConnection();
        $stmt       = $connection->prepare('SELECT * from ownerwithtypes WHERE id = ?');
        $owners     = $em->getRepository(OwnerWithTypes::class)->findAll();
        $this->assertCount(1, $owners);
        /** @var OwnerWithTypes $owner */
        $owner = $owners[0];
        $this->assertEquals($secret, $owner->getSecret());
        $this->assertEquals($notSecret, $owner->getNotSecret());
        $stmt->bindValue(1, $owner->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertEquals($notSecret, $result['notSecret']);
        $this->assertNotEquals($secret, $result['secret']);
        $this->assertStringEndsWith('<ENC>', $result['secret']);
        $decrypted = $this->encryptor->decrypt(str_replace('<ENC>', '', $result['secret']));
        $this->assertEquals($secret, $decrypted);
    }

    public function testEncryptionCascades(): void
    {
        $secret    = "It's a secret";
        $notSecret = "You're all welcome to know this.";
        $em        = $this->entityManager;
        $owner     = new OwnerWithTypes();
        $em->persist($owner); // persist cascades
        $em->flush();

        $cascadeTarget = new CascadeTargetWithTypes();
        $cascadeTarget->setSecret($secret);
        $cascadeTarget->setNotSecret($notSecret);
        $owner->setCascaded($cascadeTarget);
        $em->flush();
        $em->clear();
        unset($owner);
        unset($cascadeTarget);

        $connection     = $em->getConnection();
        $stmt           = $connection->prepare('SELECT * from cascadeTargetwithtypes WHERE id = ?');
        $cascadeTargets = $em->getRepository(CascadeTargetWithTypes::class)->findAll();
        $this->assertCount(1, $cascadeTargets);
        /** @var CascadeTargetWithTypes $cascadeTarget */
        $cascadeTarget = $cascadeTargets[0];
        $this->assertEquals($secret, $cascadeTarget->getSecret());
        $this->assertEquals($notSecret, $cascadeTarget->getNotSecret());
        $stmt->bindValue(1, $cascadeTarget->getId());
        $results = $this->executeStatementFetchAll($stmt);
        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertEquals($notSecret, $result['notSecret']);
        $this->assertNotEquals($secret, $result['secret']);
        $this->assertStringEndsWith('<ENC>', $result['secret']);
        $decrypted = $this->encryptor->decrypt(str_replace('<ENC>', '', $result['secret']));
        $this->assertEquals($secret, $decrypted);
    }

    public function testEncryptionClassTableInheritance(): void
    {
        $secretBase     = "It's a secret. On the base class.";
        $notSecretBase  = "You're all welcome to know this.  On the base class.";
        $secretChild    = "It's a secret. On the child class.";
        $notSecretChild = "You're all welcome to know this. On the child class.";
        $em             = $this->entityManager;
        $child          = new ClassTableInheritanceChildWithTypes();
        $child->setSecretBase($secretBase);
        $child->setNotSecretBase($notSecretBase);
        $child->setSecretChild($secretChild);
        $child->setNotSecretChild($notSecretChild);
        $em->persist($child);
        $em->flush();
        $em->clear();
        unset($child);

        $connection = $em->getConnection();
        $stmtBase   = $connection->prepare('SELECT * from classTableInheritanceBaseWithTypes WHERE id = ?');
        $stmtChild  = $connection->prepare('SELECT * from classTableInheritanceChildWithTypes WHERE id = ?');
        $childs     = $em->getRepository(ClassTableInheritanceBaseWithTypes::class)->findAll();
        self::assertCount(1, $childs);
        /** @var ClassTableInheritanceChildWithTypes $child */
        $child = $childs[0];
        self::assertEquals($secretBase, $child->getSecretBase());
        self::assertEquals($notSecretBase, $child->getNotSecretBase());
        self::assertEquals($secretChild, $child->getSecretChild());
        self::assertEquals($notSecretChild, $child->getNotSecretChild());

        // Now check that the fields are encrypted in the database. First the base table.
        $stmtBase->bindValue(1, $child->getId());
        $results = $this->executeStatementFetchAll($stmtBase);
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertEquals($notSecretBase, $result['notSecretBase']);
        self::assertNotEquals($secretBase, $result['secretBase']);
        self::assertStringEndsWith('<ENC>', $result['secretBase']);
        $decrypted = $this->encryptor->decrypt(str_replace('<ENC>', '', $result['secretBase']));
        self::assertEquals($secretBase, $decrypted);

        // and then the child table.
        $stmtChild->bindValue(1, $child->getId());
        $results = $this->executeStatementFetchAll($stmtChild);
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertEquals($notSecretChild, $result['notSecretChild']);
        self::assertNotEquals($secretChild, $result['secretChild']);
        self::assertStringEndsWith('<ENC>', $result['secretChild']);
        $decrypted = $this->encryptor->decrypt(str_replace('<ENC>', '', $result['secretChild']));
        self::assertEquals($secretChild, $decrypted);
    }

    public function testEncryptionDoesNotHappenWhenThereIsNoChange(): void
    {
        $secret    = "It's a secret";
        $notSecret = "You're all welcome to know this.";
        $em        = $this->entityManager;
        $owner1    = new OwnerWithTypes();
        $owner1->setSecret($secret);
        $owner1->setNotSecret($notSecret);
        $em->persist($owner1);
        $owner2 = new OwnerWithTypes();
        $owner2->setSecret($secret);
        $owner2->setNotSecret($notSecret);
        $em->persist($owner2);

        $em->flush();
        $em->clear();
        $owner1Id = $owner1->getId();
        unset($owner1);
        unset($owner2);

        // test that it was encrypted correctly
        $connection = $em->getConnection();
        $stmt       = $connection->prepare('SELECT * from ownerwithtypes WHERE id = ?');
        $stmt->bindValue(1, $owner1Id);
        $results = $this->executeStatementFetchAll($stmt);
        $this->assertCount(1, $results);
        $result             = $results[0];
        $originalEncryption = $result['secret'];
        $this->assertStringEndsWith('<ENC>', $originalEncryption); // is encrypted

        $owners = $em->getRepository(OwnerWithTypes::class)->findAll();
        /** @var OwnerWithTypes $owner */
        foreach ($owners as $owner) {
            $this->assertEquals($secret, $owner->getSecret());
            $this->assertEquals($notSecret, $owner->getNotSecret());
            $this->entityManager->getUnitOfWork()->computeChangeSets();
            $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($owner)));
        }
        $em->flush();

        $stmt->bindValue(1, $owner1Id);
        $results = $this->executeStatementFetchAll($stmt);
        $this->assertCount(1, $results);
        $result                  = $results[0];
        $shouldBeTheSameAsBefore = $result['secret'];
        $this->assertStringEndsWith('<ENC>', $shouldBeTheSameAsBefore); // is encrypted
        $this->assertEquals($originalEncryption, $shouldBeTheSameAsBefore);
    }

    public function testEncryptionDoesNotHappenWhenThereIsNoChangeClassInheritance(): void
    {
        $secretBase     = "It's a secret. On the base class.";
        $notSecretBase  = "You're all welcome to know this.  On the base class.";
        $secretChild    = "It's a secret. On the child class.";
        $notSecretChild = "You're all welcome to know this. On the child class.";
        $em             = $this->entityManager;
        $child          = new ClassTableInheritanceChildWithTypes();
        $child->setSecretBase($secretBase);
        $child->setNotSecretBase($notSecretBase);
        $child->setSecretChild($secretChild);
        $child->setNotSecretChild($notSecretChild);
        $em->persist($child);
        $em->flush();
        $em->clear();
        $childId = $child->getId();
        unset($child);

        // test that it was encrypted correctly
        $connection = $em->getConnection();
        $stmtBase   = $connection->prepare('SELECT * from classTableInheritanceBaseWithTypes WHERE id = ?');
        $stmtBase->bindValue(1, $childId);
        $result                 = $this->executeStatementFetch($stmtBase);
        $originalEncryptionBase = $result['secretBase'];
        self::assertStringEndsWith('<ENC>', $originalEncryptionBase); // is encrypted

        // do the same for the child.
        $connection = $em->getConnection();
        $stmtChild  = $connection->prepare('SELECT * from classTableInheritanceChildWithTypes WHERE id = ?');
        $stmtChild->bindValue(1, $childId);
        $result                  = $this->executeStatementFetch($stmtChild);
        $originalEncryptionChild = $result['secretChild'];
        self::assertStringEndsWith('<ENC>', $originalEncryptionChild); // is encrypted

        $childs = $em->getRepository(ClassTableInheritanceChildWithTypes::class)->findAll();
        $child  = $childs[0];
        self::assertEquals($secretBase, $child->getSecretBase());
        self::assertEquals($notSecretBase, $child->getNotSecretBase());
        self::assertEquals($secretChild, $child->getSecretChild());
        self::assertEquals($notSecretChild, $child->getNotSecretChild());

        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($child)));
        $em->flush();

        // No queries happened because we didn't change anything.
        $this->entityManager->getUnitOfWork()->computeChangeSets();
        $this->assertEquals(0, count($this->entityManager->getUnitOfWork()->getEntityChangeSet($child)));
        // flush again
        $em->flush();

        $stmtBase->bindValue(1, $childId);
        $result                      = $this->executeStatementFetch($stmtBase);
        $shouldBeTheSameAsBeforeBase = $result['secretBase'];
        self::assertStringEndsWith('<ENC>', $shouldBeTheSameAsBeforeBase); // is encrypted
        self::assertEquals($originalEncryptionBase, $shouldBeTheSameAsBeforeBase);

        $stmtChild->bindValue(1, $childId);
        $result                       = $this->executeStatementFetch($stmtChild);
        $shouldBeTheSameAsBeforeChild = $result['secretChild'];
        self::assertStringEndsWith('<ENC>', $shouldBeTheSameAsBeforeChild); // is encrypted
        self::assertEquals($originalEncryptionChild, $shouldBeTheSameAsBeforeChild);
    }

    public function testEncryptionDoesHappenWhenASecretIsChanged(): void
    {
        $secret    = "It's a secret";
        $notSecret = "You're all welcome to know this.";
        $em        = $this->entityManager;
        $owner     = new OwnerWithTypes();
        $owner->setSecret($secret);
        $owner->setNotSecret($notSecret);
        $em->persist($owner);
        $em->flush();
        $em->clear();
        $ownerId = $owner->getId();
        unset($owner);

        // test that it was encrypted correctly
        $connection = $em->getConnection();
        $stmt       = $connection->prepare('SELECT * from ownerwithtypes WHERE id = ?');
        $stmt->bindValue(1, $ownerId);
        $results = $this->executeStatementFetchAll($stmt);
        $this->assertCount(1, $results);
        $result             = $results[0];
        $originalEncryption = $result['secret'];
        $this->assertStringEndsWith('<ENC>', $originalEncryption); // is encrypted

        /** @var OwnerWithTypes $owner */
        $owner = $em->getRepository(OwnerWithTypes::class)->find($ownerId);
        $owner->setSecret('A NEW SECRET!!!');
        $em->flush();

        $stmt->bindValue(1, $ownerId);
        $results = $this->executeStatementFetchAll($stmt);
        $this->assertCount(1, $results);
        $result                      = $results[0];
        $shouldBeDifferentFromBefore = $result['secret'];
        $this->assertStringEndsWith('<ENC>', $shouldBeDifferentFromBefore); // is encrypted
        $this->assertNotEquals($originalEncryption, $shouldBeDifferentFromBefore);
    }
}
