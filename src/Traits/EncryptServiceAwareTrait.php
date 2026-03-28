<?php

namespace Ambta\DoctrineEncryptBundle\Traits;

use Ambta\DoctrineEncryptBundle\Service\EncryptServiceAwareInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait EncryptServiceAwareTrait
{
    /**
     * @var EncryptServiceAwareInterface|null
     */
    private $encryptService;

    /**
     * @required
     *
     * @return void
     */
    #[Required]
    public function setEncryptService(EncryptServiceAwareInterface $encryptService)
    {
        $this->encryptService = $encryptService;
    }

    public function getEncryptService(): ?EncryptServiceAwareInterface
    {
        return $this->encryptService;
    }
}
