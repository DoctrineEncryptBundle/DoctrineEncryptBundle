# Configuration Reference

All available configuration options are listed below.

By default there is no configuration file created. 

To make changes to the default configuration values you will need to create the yaml file config/packages/ambta_doctrine_encrypt.yaml

* **encryptor_class** - Custom class for encrypting data
    * Encryptor class, [your own encryptor class](/src/Resources/doc/custom_encryptor.md) will override encryptor paramater
    * Encryptor must implement the Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface interface
    * Default: Halite
* **secret_directory_path** - Path to where the key file is stored
    * Default: '%kernel.project_dir%'
    * If **secret** is defined in the configuration make sure that **secret_directory_path** is not
* **enable_secret_generation** - Defines if the secret is generated if not available
    * Default: true
* **secret** - The secret as a string
    * Default: null
    * Suggest to use environment variables and [Symfony Secrets](https://symfony.com/doc/current/configuration/secrets.html)
    * If **secret_directory_path** is defined in the configuration make sure that **secret** is not

## yaml examples

Using all defaults that will use Halite and a key file:

``` yaml
ambta_doctrine_encrypt:
    # If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
    # Default: Halite
    # encryptor_class: Halite

    # Path where to store the keyfiles
    # Default: '%kernel.project_dir%'
    # secret_directory_path: '%kernel.project_dir%'

    # If the secret should be generated
    # Default: true
    # enable_secret_generation: true

    # Secret string
    # secret: '%env(CRYPTO_SECRET)%'
```

Using Defuse:

``` yaml
ambta_doctrine_encrypt:
    # If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
    # Default: Halite
    encryptor_class: Defuse

    # Path where to store the keyfiles
    # Default: '%kernel.project_dir%'
    # secret_directory_path: '%kernel.project_dir%'

    # If the secret should be generated
    # Default: true
    # enable_secret_generation: true

    # Secret string
    # secret: '%env(CRYPTO_SECRET)%'
```

Using different key file path:

``` yaml
ambta_doctrine_encrypt:
    # If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
    # Default: Halite
    # encryptor_class: Halite

    # Path where to store the keyfiles
    # Default: '%kernel.project_dir%'
    secret_directory_path: '%kernel.project_dir%/keys'

    # If the secret should be generated
    # Default: true
    # enable_secret_generation: true

    # Secret string
    # secret: '%env(CRYPTO_SECRET)%'
```

Using secret string instead of key file:

``` yaml
ambta_doctrine_encrypt:
    # If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
    # Default: Halite
    # encryptor_class: Halite

    # Path where to store the keyfiles
    # Default: '%kernel.project_dir%'
    # secret_directory_path: '%kernel.project_dir%'

    # If the secret should be generated
    # Default: true
    enable_secret_generation: false

    # Secret string
    secret: '%env(CRYPTO_SECRET)%'
```

The following should **never** be used as it will throw an error:

``` yaml
ambta_doctrine_encrypt:
    # If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
    # Default: Halite
    # encryptor_class: Halite

    # Path where to store the keyfiles
    # Default: '%kernel.project_dir%'
    secret_directory_path: '%kernel.project_dir%'

    # If the secret should be generated
    # Default: true
    # enable_secret_generation: true

    # Secret string
    secret: '%env(CRYPTO_SECRET)%'
```

Be aware that there are multiple different service definitions [Yaml](/src/Resources/config).
* The services.yml file is always loaded first
* Then if the secret configuration setting is used services_with_secret.yml will be loaded otherwise services_with_secretfactory.yml
* Symfony 5 and 6 where Doctrine ORM version matches ^3.0 service_listeners_with_attributes.yml will be loaded that does not support annotations
* Symfony 5 and 6 with an older Doctrine version services_subscriber_with_annotations_and_attributes.yml will be loaded that supports both attributes and annotations
* Symfony 7 always just loads attributes only service_listeners_with_attributes.yml service definition

If has been reported that in some cases the encryption and decryption does not work as expected and the service definitions assisted in fixing the issues.

One solution that have been reported was to copy the "ambta_doctrine_encrypt.orm_subscriber" definition from the service_listeners_with_attributes.yml file into
the project's services.yaml file.

Due to the Doctrine Annotations [deprecation](https://www.doctrine-project.org/projects/doctrine-annotations/en/stable/index.html#deprecation-notice) it has been made possible to change the reader to the ambta_doctrine_attribute_reader reader only and skip using annotations completely.

Attributes are faster to read than annotations so it is definitely recommended.

Depending on PHP, Symfony and Doctrine ORM versions the optimal and supported readers between annotations, annotations and attibutes or just attributes are loaded automatically.

``` yaml
services:
    ambta_doctrine_encrypt.subscriber:
        alias: ambta_doctrine_encrypt.orm_subscriber

    ambta_doctrine_encrypt.encrypt_service:
        class: Ambta\DoctrineEncryptBundle\Service\EncryptService
        arguments:
            - "@doctrine.orm.entity_manager"
            - "@ambta_doctrine_encrypt.encryptor"

    Ambta\DoctrineEncryptBundle\Service\EncryptServiceAwareInterface: '@ambta_doctrine_encrypt.encrypt_service'

    ambta_doctrine_encrypt.command.decrypt.database:
        class: Ambta\DoctrineEncryptBundle\Command\DoctrineDecryptDatabaseCommand
        tags: ['console.command']
        arguments:
            - "@doctrine.orm.entity_manager"
            - "@ambta_doctrine_annotation_reader"
            - "@ambta_doctrine_encrypt.subscriber"
            - "@ambta_doctrine_encrypt.encrypt_service"

    ambta_doctrine_encrypt.command.encrypt.database:
        class: Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptDatabaseCommand
        tags: ['console.command']
        arguments:
            - "@doctrine.orm.entity_manager"
            - "@ambta_doctrine_annotation_reader"
            - "@ambta_doctrine_encrypt.subscriber"
            - "@ambta_doctrine_encrypt.encrypt_service"

    ambta_doctrine_encrypt.command.encrypt.status:
        class: Ambta\DoctrineEncryptBundle\Command\DoctrineEncryptStatusCommand
        tags: ['console.command']
        arguments:
            - "@doctrine.orm.entity_manager"
            - "@ambta_doctrine_annotation_reader"
            - "@ambta_doctrine_encrypt.subscriber"
            - "@ambta_doctrine_encrypt.encrypt_service"
```

## Important!

If you want to use Defuse, make sure to require it!

composer require "defuse/php-encryption ^2.0"

## Usage

Read how to use the database encryption bundle in your project.
#### [Usage](/src/Resources/doc/usage.md)
