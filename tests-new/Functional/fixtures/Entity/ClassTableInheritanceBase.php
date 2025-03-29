<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\InheritanceType("JOINED")
 *
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
class ClassTableInheritanceBase
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted()
     *
     * @ORM\Column(type="string", nullable=true)
     */
    #[Encrypted]
    #[ORM\Column(type: 'string', nullable: true)]
    private $secretBase;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $notSecretBase;

    public function getId()
    {
        return $this->id;
    }

    public function getSecretBase()
    {
        return $this->secretBase;
    }

    public function setSecretBase($secretBase)
    {
        $this->secretBase = $secretBase;
    }

    public function getNotSecretBase()
    {
        return $this->notSecretBase;
    }

    public function setNotSecretBase($notSecretBase)
    {
        $this->notSecretBase = $notSecretBase;
    }
}
