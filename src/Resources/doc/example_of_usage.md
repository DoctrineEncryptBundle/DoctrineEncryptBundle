# Example Of Usage

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

// importing @Encrypted annotation
use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_v")
 */
class UserV {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="text", name="total_money")
     * @Encrypted
     * @var int
     */
    private $totalMoney;

    /**
     * @ORM\Column(type="encrypted", name="total_money")
     * @var int
     */
    private $totalMoneyTypeExample;

    /**
     * @ORM\Column(type="string", length=100, name="first_name")
     * @var string
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=100, name="last_name")
     * @var string
     */
    private $lastName;

    /**
     * @ORM\Column(type="text", name="credit_card_number")
     * @Encrypted
     * @var string
     */
    private $creditCardNumber;

    //common getters/setters here...

}
```

### Fixtures

```php

namespace Acme\DemoBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Acme\DemoBundle\Entity\UserV;

class LoadUserData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = new UserV();
        $user->setFirstName('Victor');
        $user->setLastName('Melnik');
        $user->setTotalMoney(20);
        $user->setCreditCardNumber('1234567890');

        $manager->persist($user);
        $manager->flush();
    }
}
```

### Controller

```php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

// our entity
use Acme\DemoBundle\Entity\UserV;

class DemoController extends Controller
{
    /**
     * @Route("/show-user/{id}", name="_ambta_decrypt_test", requirements={"id" = "\d+"})
     * @Template
     */
    public function getUserAction(UserV $user) {}
}
```

### Template

```twig
<div>Common info: {{ user.lastName ~  ' ' ~ user.firstName }}</div>
<div>
    Decoded info:
    <dl>
        <dt>Total money<dt>
        <dd>{{ user.totalMoney }}</dd>
        <dt>Credit card<dt>
        <dd>{{ user.creditCardNumber }}</dd>
    </dl>
</div>
```

When we follow link /show-user/{x}, where x - id of our user in DB, we will see that
user's information is decoded and in the same time information in database will
be encrypted. In database we'll have something like this:

```
id                  | 1
total_money         | def50200100cd243434bc5fbbe5ecc87c153cda9d62e4c2f5ffb27c29b37df0cacd6d4a4b51408b3cefa950ea6b7ed22ab3b98344c8723f5ccee9c6d0aca8f48169c175bbdaba96d8c8106f1132ba5774954434a030df00771<ENC>
first_name          | Victor
last_name           | Melnik
credit_card_number  | def50200af8d084c22099d29b3940334de4c5c57df8517934dfd567e2d04f9a16a60e455690ab5e118ad007054845351df31a9d9370fdfac97ebdeb3e9589e3a1c094202e715c5c1607acb24667a1a3981e2fa626058a8d8<ENC>
```

So our information is encrypted, and unless someone has your .DefuseEncryptor.key file they cannot access this information.

### Requirements

You need `DoctrineFixturesBundle` and `defuse/php-encryption` extension for this example

#### [Back to index](https://github.com/DoctrineEncryptBundle/DoctrineEncryptBundle/blob/master/src/Resources/doc/index.md)
