<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Functional\fixtures\Entity;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
#[ORM\Entity]
class ClassTableInheritanceChild extends ClassTableInheritanceBase
{
    /**
     * @DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted()
     *
     * @ORM\Column(type="string", nullable=true)
     */
    #[Encrypted]
    #[ORM\Column(type: 'string', nullable: true)]
    private $secretChild;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $notSecretChild;

    public function getSecretChild()
    {
        return $this->secretChild;
    }

    public function setSecretChild($secretChild)
    {
        $this->secretChild = $secretChild;
    }

    public function getNotSecretChild()
    {
        return $this->notSecretChild;
    }

    public function setNotSecretChild($notSecretChild)
    {
        $this->notSecretChild = $notSecretChild;
    }
}
