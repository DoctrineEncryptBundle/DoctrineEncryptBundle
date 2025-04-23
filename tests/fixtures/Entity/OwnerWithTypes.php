<?php

namespace Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
#[ORM\Entity()]
class OwnerWithTypes
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
    private $secret;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $notSecret;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity\CascadeTargetWithTypes",
     *     cascade={"persist"})
     */
    #[ORM\OneToOne(targetEntity: CascadeTargetWithTypes::class, cascade: ['persist'])]
    private $cascaded;

    public function getId()
    {
        return $this->id;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    public function getNotSecret()
    {
        return $this->notSecret;
    }

    public function setNotSecret($notSecret)
    {
        $this->notSecret = $notSecret;
    }

    public function getCascaded()
    {
        return $this->cascaded;
    }

    public function setCascaded($cascaded)
    {
        $this->cascaded = $cascaded;
    }
}
