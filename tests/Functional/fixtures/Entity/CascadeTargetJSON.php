<?php

namespace DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class CascadeTargetJSON
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type:"integer")]
    #[ORM\GeneratedValue()]
    private $id;

    #[ORM\Column(type:"encrypted_json", nullable:true)]
    private $secret;

    #[ORM\Column(type:"string", nullable:true)]
    private $notSecret;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param mixed $secret
     */
    public function setSecret($secret): void
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
    public function setNotSecret($notSecret): void
    {
        $this->notSecret = $notSecret;
    }
}