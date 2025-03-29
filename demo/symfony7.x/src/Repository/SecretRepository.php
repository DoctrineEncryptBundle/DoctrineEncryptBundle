<?php

namespace DoctrineEncryptBundle\Demo\Symfony7x\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use DoctrineEncryptBundle\Demo\Symfony7x\Entity\Secret;

/**
 * @method Secret|null find($id, $lockMode = null, $lockVersion = null)
 * @method Secret|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Secret[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class SecretRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Secret::class);
    }
}
