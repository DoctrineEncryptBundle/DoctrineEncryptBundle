<?php


namespace DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Owner
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column(type:"integer")]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @ORM\Column(type="encrypted", nullable=true)
     */
    #[ORM\Column(type:"encrypted", nullable:true)]
    private $secret;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type:"string", nullable:true)]
    private $notSecret;

     /**
     * @ORM\OneToOne(
     *     targetEntity="DoctrineEncryptBundle\Tests\Functional\fixtures\Entity\CascadeTarget",
     *     cascade={"persist"})
     */
    #[ORM\OneToOne(targetEntity:CascadeTarget::class, cascade:["persist"])]
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

    /**
     * @return mixed
     */
    public function getNotSecret()
    {
        return $this->notSecret;
    }

    /**
     * @param mixed $notSecret
     */
    public function setNotSecret($notSecret)
    {
        $this->notSecret = $notSecret;
    }

    /**
     * @return mixed
     */
    public function getCascaded()
    {
        return $this->cascaded;
    }

    /**
     * @param mixed $cascaded
     */
    public function setCascaded($cascaded)
    {
        $this->cascaded = $cascaded;
    }

}