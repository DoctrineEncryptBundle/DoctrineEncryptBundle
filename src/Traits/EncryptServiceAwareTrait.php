<?php

namespace Ambta\DoctrineEncryptBundle\Traits;

use Ambta\DoctrineEncryptBundle\Service\EncryptServiceAwareInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait EncryptServiceAwareTrait
{
    /**
     * @var EncryptServiceAwareInterface|null
     */
    private $encryptService;

    /**
     * @var EntityManagerInterface|null
     */
    private $entityManager;

    /**
     * @return void
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @required
     *
     * @return void
     */
    #[Required]
    public function setEncryptService(EncryptServiceAwareInterface $encryptService)
    {
        $this->encryptService = $encryptService;
    }

    public function getEncryptService(): ?EncryptServiceAwareInterface
    {
        return $this->encryptService;
    }
}
