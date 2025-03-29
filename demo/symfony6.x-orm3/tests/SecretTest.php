<?php

namespace DoctrineEncryptBundle\Demo\Symfony6xOrm3\Tests;

use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineEncryptBundle\Demo\Symfony6xOrm3\Entity\Secret;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecretTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel([]);
    }

    public function testSecretsAreEncryptedInDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // Make sure we do not store testdata
        $entityManager->beginTransaction();

        $name         = 'test123';
        $secretString = 'i am a secret string';

        // Create entity to test with
        $newSecretObject = (new Secret())
            ->setName($name)
            ->setSecret($secretString);

        $entityManager->persist($newSecretObject);
        $entityManager->flush();

        // Fetch the actual data
        $secretRepository = $entityManager->getRepository(Secret::class);
        $qb               = $secretRepository->createQueryBuilder('s');
        $qb->select('s')
            ->addSelect('(s.secret) as rawSecret')
            ->where('s.name = :name')
            ->setParameter('name', $name)
            ->orderBy('s.name', 'ASC');
        $result = $qb->getQuery()->getSingleResult();

        $actualSecretObject = $result[0];
        $actualRawSecret    = $result['rawSecret'];

        self::assertInstanceOf(Secret::class, $actualSecretObject);
        self::assertEquals($newSecretObject->getSecret(), $actualSecretObject->getSecret());
        self::assertEquals($newSecretObject->getName(), $actualSecretObject->getName());
        // Make sure it is encrypted
        self::assertNotEquals($newSecretObject->getSecret(), $actualRawSecret);
        self::assertStringEndsWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $actualRawSecret);
    }
}
