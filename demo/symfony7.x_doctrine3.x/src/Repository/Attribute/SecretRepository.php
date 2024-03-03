<?php

namespace App\Repository\Attribute;

use App\Entity\Attribute\Secret;
use App\Repository\AbstractSecretRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method Secret|null find($id, $lockMode = null, $lockVersion = null)
 * @method Secret|null findOneBy(array $criteria, array $orderBy = null)
 * @method Secret[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecretRepository extends AbstractSecretRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Secret::class);
    }
}