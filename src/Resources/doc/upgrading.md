# Upgrading to 6.x
## Breaking changes
* Instead of exceptions directly from halite or defuse, you now get a `\DoctrineEncryptCommunity\DoctrineEncryptBundle\Exception\UnableToEncryptException` 
  or a `\DoctrineEncryptCommunity\DoctrineEncryptBundle\Exception\UnableToDecryptException`, which both extend `\DoctrineEncryptCommunity\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException`.
* Throw a `\DoctrineEncryptCommunity\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException` in case something goes wrong encrypting/decrypting 

## Suggested changes
* Switch from using Annotations or Attributes to use Doctrine custom types. Details available at [Usage](/src/Resources/doc/usage.md)
  Specifically note that using Doctrine custom types no special Entity Method Behaviour will be required inside the Entity any longer.

#### [To index](/src/Resources/doc/index.md)
