<?php

use DoctrineEncryptBundle\DoctrineEncryptBundle\Rector\Set\DoctrineEncryptBundleSetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/demo/symfony6.x/src',
        __DIR__.'/demo/symfony6.x/config',
        __DIR__.'/demo/symfony6.x/tests',
        __DIR__.'/demo/symfony6.x-orm3/src',
        __DIR__.'/demo/symfony6.x-orm3/config',
        __DIR__.'/demo/symfony6.x-orm3/tests',
        __DIR__.'/demo/symfony7.x/src',
        __DIR__.'/demo/symfony7.x/config',
        __DIR__.'/demo/symfony7.x/tests',
    ])
    ->withSets([
        DoctrineEncryptBundleSetList::TO_DOCTRINE_ENCRYPT_BUNDLE_NAMESPACE,
    ]);
