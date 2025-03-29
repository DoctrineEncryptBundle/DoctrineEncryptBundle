<?php

namespace Ambta\DoctrineEncryptBundle\Traits;

use Ambta\DoctrineEncryptBundle\Service\Encrypt;

trait DoctrineEncrypt
{
    /**
     * @var Encrypt|null
     */
    protected $service;

    /**
     * @return void
     */
    public function setService(Encrypt $service)
    {
        $this->service = $service;
    }
}
