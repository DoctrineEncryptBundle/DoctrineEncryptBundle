<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class DateTimeJsonArrayTarget
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
     * @DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted(type="datetime")
     *
     * @ORM\Column(type="string", nullable=true)
     */
    #[Encrypted(type: 'datetime')]
    #[ORM\Column(type: 'string', nullable: true)]
    private $date;

    /**
     * @DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted(type="json")
     *
     * @ORM\Column(type="string", nullable=true)
     */
    #[Encrypted(type: 'json')]
    #[ORM\Column(type: 'string', nullable: true)]
    private $json;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function setJson($json): void
    {
        $this->json = $json;
    }
}
