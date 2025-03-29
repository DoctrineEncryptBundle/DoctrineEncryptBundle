<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        MicroKernelTrait::registerContainerConfiguration as microKernelRegisterContainerConfiguration;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $this->microKernelRegisterContainerConfiguration($loader);

        if (PHP_VERSION_ID >= 80000) {
            $loader->load($this->getConfigDir().'/{packages}/php8/*.{yml,yaml}', 'glob');
        }
    }
}
