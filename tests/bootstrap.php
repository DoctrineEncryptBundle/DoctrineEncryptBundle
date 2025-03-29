<?php

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using composer to run the test suite.');
}

$autoload = require $file;

// Allowing mocking final classes
DG\BypassFinals::enable(false, true);


if (method_exists(Doctrine\Common\Annotations\AnnotationRegistry::class, 'registerLoader')) {
    Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$autoload, 'loadClass']);
}
