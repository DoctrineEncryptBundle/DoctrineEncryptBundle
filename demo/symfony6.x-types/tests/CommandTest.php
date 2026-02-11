<?php

namespace App\Tests;

use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel([]);
    }

    private function secretsAreDecryptedInDatabase(string $className)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $name         = 'test_command_'.self::$kernel::VERSION.'_'.PHP_VERSION_ID;
        $secretString = 'i am a secret string';

        $secretRepository = $entityManager->getRepository($className);
        $qb               = $secretRepository->createQueryBuilder('s');
        $qb->select('s')
            ->addSelect('(s.secret) as rawSecret')
            ->where('s.name = :name')
            ->setParameter('name', $name)
            ->orderBy('s.name', 'ASC');
        $result = $qb->getQuery()->getOneOrNullResult();
        if (is_null($result)) {
            // Create entity to test with
            $newSecretObject = (new $className())
                ->setName($name)
                ->setSecret($secretString);

            $entityManager->persist($newSecretObject);
            $entityManager->flush();

            $result = $qb->getQuery()->getOneOrNullResult();
        }

        $actualSecretObject = $result[0];
        $actualRawSecret    = $result['rawSecret'];

        self::assertInstanceOf($className, $actualSecretObject);
        self::assertEquals($secretString, $actualSecretObject->getSecret());
        self::assertEquals($name, $actualSecretObject->getName());
        // Make sure it is encrypted
        self::assertNotEquals($secretString, $actualRawSecret);
        self::assertStringEndsWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $actualRawSecret);

        $application = new Application(self::$kernel);

        $command       = $application->find('doctrine:decrypt:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--answer' => 'y',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $result             = $qb->getQuery()->getSingleResult();
        $actualSecretObject = $result[0];
        $actualRawSecret    = $result['rawSecret'];

        self::assertInstanceOf($className, $actualSecretObject);
        self::assertEquals($secretString, $actualRawSecret);

        self::assertStringEndsNotWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $actualRawSecret);
    }

    private function secretsAreEncryptedInDatabase(string $className)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $name         = 'test_command_'.self::$kernel::VERSION.'_'.PHP_VERSION_ID;
        $secretString = 'i am a secret string';

        // Fetch the actual data
        $secretRepository = $entityManager->getRepository($className);
        $qb               = $secretRepository->createQueryBuilder('s');
        $qb->select('s')
            ->addSelect('(s.secret) as rawSecret')
            ->where('s.name = :name')
            ->setParameter('name', $name)
            ->orderBy('s.name', 'ASC');
        $result = $qb->getQuery()->getSingleResult();

        $actualSecretObject = $result[0];
        $actualRawSecret    = $result['rawSecret'];

        self::assertInstanceOf($className, $actualSecretObject);
        self::assertEquals($secretString, $actualRawSecret);

        self::assertStringEndsNotWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $actualRawSecret);

        $application = new Application(self::$kernel);

        $command       = $application->find('doctrine:encrypt:database');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--answer' => 'y',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $result = $qb->getQuery()->getSingleResult();

        $actualSecretObject = $result[0];
        $actualRawSecret    = $result['rawSecret'];

        self::assertInstanceOf($className, $actualSecretObject);
        self::assertEquals($secretString, $actualSecretObject->getSecret());
        // Make sure it is encrypted
        self::assertNotEquals($secretString, $actualRawSecret);
        self::assertStringEndsWith(DoctrineEncryptSubscriber::ENCRYPTION_MARKER, $actualRawSecret);
    }

    /**
     * @covers \Entity\Annotation\Secret::getSecret
     * @covers \Entity\Annotation\Secret::getName
     */
    public function testAnnotationSecretsAreEncryptedInDatabase(): void
    {
        $this->secretsAreDecryptedInDatabase(Entity\Annotation\Secret::class);
        $this->secretsAreEncryptedInDatabase(Entity\Annotation\Secret::class);
    }

    /**
     * @covers \Entity\Attribute\Secret::getSecret
     * @covers \Entity\Attribute\Secret::getName
     *
     * @requires PHP 8.0
     */
    public function testAttributeSecretsAreEncryptedInDatabase(): void
    {
        $this->secretsAreDecryptedInDatabase(Entity\Attribute\Secret::class);
        $this->secretsAreEncryptedInDatabase(Entity\Attribute\Secret::class);
    }
}
