<?php

namespace DoctrineEncryptBundle\Demo\Symfony6x\Entity;

interface SecretInterface
{
    public function getType();

    public function getName();

    public function getSecret();

    public function getRawSecret();
}
