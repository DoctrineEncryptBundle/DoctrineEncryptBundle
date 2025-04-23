<?php

namespace Ambta\DoctrineEncryptBundle\Tests\fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
#[ORM\Entity]
class ClassTableInheritanceChildWithTypes extends ClassTableInheritanceBaseWithTypes
{
    /**
     * @ORM\Column(type="encrypted", nullable=true)
     */
    #[ORM\Column(type: 'encrypted', nullable: true)]
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
