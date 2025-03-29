<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Tests\Unit\Subscribers\fixtures;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Encrypted;

class User
{
    /**
     * @var string
     *
     * @Encrypted()
     */
    public $name;

    /**
     * @var string|null
     *
     * @Encrypted()
     */
    private $address;

    public function __construct(string $name, ?string $address)
    {
        $this->name    = $name;
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }
}
