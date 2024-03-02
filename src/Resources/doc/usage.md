# Usage

### Entity

``` php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

// importing @Encrypted annotation
use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User {

    ..

    /**
     * @ORM\Column(type="string", name="email")
     * @Encrypted
     * @var int
     */
    private $email;

    ..

}
```

It is as simple as that, the field will now be encrypted the first time the users entity gets edited.
We keep an <ENC> prefix to check if data is encrypted or not so, unencrypted data will still work even if the field is encrypted.

### Entity Method Behaviour

The bundle will not know what the entity methods do in the getter and setter so if there are any additional behaviour.
Example using strtoupper in the setter for a value the entity itself will need to know how to manage encrypted vs unencrypted data.

``` php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

// importing @Encrypted annotation
use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User {

    ..

    /**
     * @ORM\Column(type="string", name="email")
     * @Encrypted
     * @var string
     */
    private $email;

    ..

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        if (substr($email, -strlen(DoctrineEncryptSubscriber::ENCRYPTION_MARKER)) != DoctrineEncryptSubscriber::ENCRYPTION_MARKER)
        {
            $email = strtoupper ($email);
        }

        $this->email = $email;
    }

    ..
}
```

## Console commands

There are some console commands that can help you encrypt your existing database or change encryption methods.
Read more about the database encryption commands provided with this bundle.

#### [Console commands](https://github.com/DoctrineEncryptBundle/DoctrineEncryptBundle/blob/master/src/Resources/doc/commands.md)
