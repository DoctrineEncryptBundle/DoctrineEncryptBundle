<?php

namespace App\Repository\Annotation;

use App\Entity\Annotation\Secret;
use App\Repository\AbstractSecretRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Secret|null find($id, $lockMode = null, $lockVersion = null)
 * @method Secret|null findOneBy(array $criteria, array $orderBy = null)
 * @method Secret[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecretRepository extends AbstractSecretRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Secret::class);
    }
}
