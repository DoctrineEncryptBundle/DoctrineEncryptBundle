<?php

namespace DoctrineEncryptBundle\Traits;

use DoctrineEncryptBundle\Service\Encrypt;

trait DoctrineEncrypt
{
    /**
     * @var Encrypt|null
     */
    protected $service;

    /**
     * @param Encrypt $service
     * 
     * @return void
     */
    public function setService(Encrypt $service = null)
    {
        $this->service = $service;
    }
}
