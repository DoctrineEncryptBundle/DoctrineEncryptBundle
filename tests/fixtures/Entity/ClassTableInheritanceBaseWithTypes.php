<?php

namespace Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity;

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
class ClassTableInheritanceBaseWithTypes
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
     * @ORM\Column(type="encrypted", nullable=true)
     */
    #[ORM\Column(type: 'encrypted', nullable: true)]
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
