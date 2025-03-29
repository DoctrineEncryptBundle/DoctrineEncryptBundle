<?php

namespace DoctrineEncryptBundle\Demo\Symfony6xOrm3\Entity;

interface SecretInterface
{
    public function getType();

    public function getName();

    public function getSecret();

    public function getRawSecret();
}
