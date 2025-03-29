# Upgrading to 5.5

## Migrating to the New Namespace

The library is transitioning to a new namespace to align with the organization managing the bundle. This migration is optional in version 5.5 but will be required for version 6.0.

To migrate your project to the new namespace, follow these steps:

1. **Update `bundles.php`**:
   - Replace `\Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle` with `\DoctrineEncryptBundle\DoctrineEncryptBundle\DoctrineEncryptBundle`.

2. **Update Configuration Key**:
   - In `config/packages/ambta_doctrine_encrypt.yaml`, change the configuration key from `ambta_doctrine_encrypt` to `doctrine_encrypt`.

3. **Rename Configuration File**:
   - Rename `ambta_doctrine_encrypt.yaml` to `doctrine_encrypt.yaml`.

4. **Update Namespace**:
   - Within your project, replace the namespace `Ambta\DoctrineEncryptBundle` with `DoctrineEncryptBundle\DoctrineEncryptBundle`.
   - This can be automated using [Rector](https://github.com/rectorphp/rector) with the following configuration:
     ```php
     <?php
 
     use DoctrineEncryptBundle\DoctrineEncryptBundle\Rector\Set\DoctrineEncryptBundleSetList;
     use Rector\Config\RectorConfig;
 
     return RectorConfig::configure()
       ->withPaths([
         __DIR__.'/src/',
         __DIR__.'/tests/'
       ])
       ->withSets([
           DoctrineEncryptBundleSetList::TO_DOCTRINE_ENCRYPT_BUNDLE_NAMESPACE,
       ]);
     ```

5. **Update Custom Services/Configurations**:
   - If your project has custom services or configurations based on the bundle's services or parameters, update them to use the new names.

   | Type      | Old                                             | New                                       |
    |-----------|-------------------------------------------------|-------------------------------------------|
   | Parameter | `ambta_doctrine_encrypt.secret`                 | `doctrine_encrypt.secret`                 |
   | Parameter | `ambta_doctrine_encrypt.encryptor_class_name`   | `doctrine_encrypt.encryptor.class_name`   |
   | Parameter | `ambta_doctrine_encrypt.enable_secret_generation` | `doctrine_encrypt.secret.enable_generation` |
   | Parameter | `ambta_doctrine_encrypt.secret_directory_path`  | `doctrine_encrypt.secret.directory_path`  |
   | Parameter | `ambta_doctrine_encrypt.supported_encryptors`   | `doctrine_encrypt.supported_encryptors`   |
   | Service   | `ambta_doctrine_encrypt.encryptor`              | `doctrine_encrypt.encryptor`              |
   | Service   | `ambta_doctrine_encrypt.secret_factory`         | `doctrine_encrypt.secret.factory`         |
   | Service   | `ambta_doctrine_annotation_reader`              | `doctrine_encrypt.annotations.reader`     |
   | Service   | `ambta_doctrine_attribute_reader`               | `doctrine_encrypt.attributes.reader`      |
   | Service   | `ambta_doctrine_encrypt.orm_subscriber`         | `doctrine_encrypt.orm.subscriber`         |
   | Service   | `ambta_doctrine_encrypt.command.decrypt.database` | `doctrine_encrypt.command.decrypt_database` |
   | Service   | `ambta_doctrine_encrypt.command.encrypt.database` | `doctrine_encrypt.command.encrypt_database` |
   | Service   | `ambta_doctrine_encrypt.command.encrypt.status` | `doctrine_encrypt.command.encrypt_status` |

Additionally, some classes have been made final to facilitate refactoring, as they will no longer be extendable.

## Bundle-Specific Exceptions

The library will now throw bundle-specific exceptions:
- `\DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToEncryptException`
- `\DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\UnableToDecryptException`

Both exceptions extend `\DoctrineEncryptBundle\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException`.

This change is optional in version 5.5 but will be required for version 6.0. You can opt in by setting `doctrine_encrypt.wrap_exceptions` to `true`.

# Upgrading to 6.0 (Not Released Yet)

## Breaking Changes

- The library will throw bundle-specific exceptions.
- The library will use the namespace `\DoctrineEncryptBundle\DoctrineEncryptBundle`, service prefix `doctrine_encrypt`, and configuration namespace `doctrine_encrypt`.
