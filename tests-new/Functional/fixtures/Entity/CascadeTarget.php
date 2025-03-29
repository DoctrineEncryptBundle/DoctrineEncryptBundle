<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class CascadeTarget
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

    public function setNotSecret($notSecret): void
    {
        $this->notSecret = $notSecret;
    }
}
