<?php

namespace DoctrineEncryptBundle\Demo\Symfony6xOrm3\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineEncryptBundle\Demo\Symfony6xOrm3\Repository\SecretRepository;
use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;

#[ORM\Table(name: 'secrets_using_attributes')]
#[ORM\Entity(repositoryClass: SecretRepository::class)]
class Secret implements SecretInterface
{
    #[ORM\Id()]
    #[ORM\Column(type: 'string', nullable: false)]
    private $name;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Encrypted]
    private $secret;

    /**
     * Used to fill in value from repo.
     *
     * @var string
     */
    private $rawSecret;

    public function getType()
    {
        return 'Attribute';
    }

    /**
     * return mixed.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     *
     * @return $this
     */
    public function setSecret($secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getRawSecret()
    {
        return $this->rawSecret;
    }

    /**
     * @return $this
     */
    public function setRawSecret($rawSecret)
    {
        $this->rawSecret = $rawSecret;

        return $this;
    }
}
