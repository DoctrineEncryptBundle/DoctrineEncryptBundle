# Usage

### Column Type

Using column types is suggested to use the bundles supported encryption types.  All supported types automatically linked to either the string or text database type.

Supported types:

* encrypted
* encrypted_array
* encrypted_datetime
* encrypted_json

Ensure that the column type for the property is always set to string or text when using attributes or annotations.

The column type should always relate back to one of the database supported string types as the encrypted data saved to the database will always be a string.

### Entity

Using supported type:

``` php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User {

    ..

    /**
     * @ORM\Column(type="encrypted", name="email")
     * @var int
     */
    private $email;

    ..

}
```

Using annotation:

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
We keep an <ENC> suffix to check if data is encrypted or not so, unencrypted data will still work even if the field is encrypted.

#### Supported Data Types

Relevant when using annotations or attributes.

The supported data types to be encrypted and decrypted are:
* string (The Default)
* datetime
* json
* array

Example usage in the Entity:

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

// importing @Encrypted annotation
use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;

/**
 * @ORM\Entity
 * @ORM\Table(name="test")
 */
class Test {

    ..

    /**
     * @ORM\Column(type="string", name="string")
     * @Encrypted
     * @var string
     */
    private $string;

    /**
     * @ORM\Column(type="string", name="another_string")
     * @Encrypted(type="string")
     * @var string
     */
    private $anotherString;

    /**
     * @ORM\Column(type="text", name="datetime")
     * @Encrypted(type="datetime")
     * @var DateTime
     */
    private $datetime;

    /**
     * @ORM\Column(type="text", name="json")
     * @Encrypted(type="json")
     * @var array
     */
    private $json;

    /**
     * @ORM\Column(type="text", name="array")
     * @Encrypted(type="array")
     * @var array
     */
    private $array;

    ..

}
```

Please note again that the ORM Column types relate to string values as the saved data whould be the encrypted string.
If using MySql for example you will not be able to use the JSON functions directly in the database when the json data is encrypted.

### Entity Method Behaviour

When using the bundle supported types:

The bundle does not need to know what the entity methods do in the getter and setter. 
The values are only encrypted right before they are inserted or updated in the database and decrypted right after they were retrieved.

Example using strtoupper in the setter for a value when using the bundle supported types.

``` php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User {

    ..

    /**
     * @ORM\Column(type="encrypted", name="email")
     * @var string
     */
    private $email;

    ..

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = strtoupper($email);
    }

    ..
}
```

When using annotations or attributes:

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

#### [Console commands](/src/Resources/doc/commands.md)
