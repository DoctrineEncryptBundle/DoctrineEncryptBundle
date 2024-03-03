<?php
namespace DoctrineEncryptBundle\Command;

use DoctrineEncryptBundle\Service\Encrypt;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base command containing usefull base methods.
 **/
abstract class AbstractCommand extends Command
{
    /**
     * @var EntityManagerInterface|EntityManager
     */
    protected $entityManager;

    /**
     * @var Encrypt
     */
    protected $service;

    /**
     * @param ContainerInterface $container
     * 
     * @return void
     */
    public function __construct(EntityManagerInterface $entityManager, Encrypt $service)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->service = $service;
    }

    /**
     * Get an result iterator over the whole table of an entity.
     *
     * @param string $entityName
     * @return iterable|array
     */
    protected function getEntityIterator(string $entityName): iterable
    {
        $query = $this->entityManager->createQuery(sprintf('SELECT o FROM %s o', $entityName));

        return $query->toIterable();
    }

    /**
     * Get the number of rows in an entity-table
     *
     * @param string $entityName
     *
     * @return int
     */
    protected function getTableCount(string $entityName): int
    {
        $query = $this->entityManager->createQuery(sprintf('SELECT COUNT(o) FROM %s o', $entityName));

        return (int) $query->getSingleScalarResult();
    }
}
