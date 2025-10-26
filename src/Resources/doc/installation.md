# Installation

1. Download DoctrineEncryptBundle using composer
2. Enable the database encryption bundle
3. Configure the database encryption bundle

* [Upgrading](/src/Resources/doc/upgrading.md)

### Requirements

 - PHP ^8.1
 - Comes with package: [Halite](https://github.com/paragonie/halite) ^4.6 || ^5.0
 - [doctrine/orm](https://packagist.org/packages/doctrine/orm) ^2.12 || ^3.3

### Step 1: Download DoctrineEncryptBundle using composer

DoctrineEncryptBundle should be installed using [Composer](http://getcomposer.org/):

``` js
php composer.phar require "doctrineencryptbundle/doctrine-encrypt-bundle:^6.0"
```

Composer will install the bundle to your project's `vendor/doctrineencryptbundle` directory.

### Step 2: Enable the bundle

Enable the bundle manually, if not using Symfony flex, in Symfony by adding it in your config/bundles.php file's return statement:

``` php
Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle::class => ['all' => true]
```

### Step 3: Set your configuration

All configuration value's are optional.
On the following page you can find the configuration information.

#### [Configuration](/src/Resources/doc/configuration.md)
