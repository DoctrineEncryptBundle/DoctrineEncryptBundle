<?php

namespace DoctrineEncryptBundle\Demo\Symfony7x\Entity;

interface SecretInterface
{
    public function getType();

    public function getName();

    public function getSecret();

    public function getRawSecret();
}
