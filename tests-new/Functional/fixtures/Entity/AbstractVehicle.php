<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 *
 * @ORM\DiscriminatorColumn(name="type", type="string")
 *
 * @ORM\DiscriminatorMap({"car" = "VehicleCar","bike" = "VehicleBicycle"})
 */
#[ORM\Entity()]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['car' => 'VehicleCar', 'bike' => 'VehicleBicycle'])]
abstract class AbstractVehicle
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
    #[\DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted]
    #[ORM\Column(type: 'string', nullable: true)]
    private $secret;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $notSecret;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function setSecret($secret): void
    {
        $this->secret = $secret;
    }

    public function getNotSecret()
    {
        return $this->notSecret;
    }

    /**
     * @return $this
     */
    public function setNotSecret($notSecret): self
    {
        $this->notSecret = $notSecret;

        return $this;
    }
}
