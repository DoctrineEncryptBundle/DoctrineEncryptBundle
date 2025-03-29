<?php

declare(strict_types=1);

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Rector\Set;

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

final class DoctrineEncryptBundleSetList
{
    /** @var string */
    public const TO_DOCTRINE_ENCRYPT_BUNDLE_NAMESPACE = __DIR__.'/../../../config/rector/to_doctrine_encrypt_namespace.php';
}