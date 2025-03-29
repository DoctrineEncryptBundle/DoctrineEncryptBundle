<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class VehicleCar extends AbstractVehicle
{
    /**
     * @ORM\Column(type="string", length=10)
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 10)]
    private $licensePlate;

    /**
     * @return string
     */
    public function getLicensePlate()
    {
        return $this->licensePlate;
    }

    /**
     * @param string $licensePlate
     *
     * @return $this
     */
    public function setLicensePlate($licensePlate): self
    {
        $this->licensePlate = $licensePlate;

        return $this;
    }
}
