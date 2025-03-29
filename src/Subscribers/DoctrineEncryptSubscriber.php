<?php

namespace Ambta\DoctrineEncryptBundle\Subscribers;

use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Ambta\DoctrineEncryptBundle\Exception\DoctrineEncryptBundleException;
use Ambta\DoctrineEncryptBundle\Mapping\AttributeReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities.
 */
class DoctrineEncryptSubscriber implements EventSubscriber
{
    /**
     * Appended to end of encrypted value.
     */
    public const ENCRYPTION_MARKER = '<ENC>';

    /**
     * Encryptor interface namespace.
     */
    public const ENCRYPTOR_INTERFACE_NS = 'Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface';

    /**
     * Encrypted annotation full name.
     */
    public const ENCRYPTED_ANN_NAME = 'Ambta\DoctrineEncryptBundle\Configuration\Encrypted';

    /**
     * Encryptor.
     *
     * @var EncryptorInterface|null
     */
    private $encryptor;

    /**
     * Annotation reader.
     *
     * @var Reader|AttributeReader
     */
    private $annReader;

    /**
     * Used for restoring the encryptor after changing it.
     *
     * @var EncryptorInterface|string
     */
    private $restoreEncryptor;

    /**
     * Used for restoring the encryptor after changing it.
     *
     * @var PropertyAccessorInterface|string
     */
    private $pac;

    /**
     * Count amount of decrypted values in this service.
     *
     * @var int
     */
    public $decryptCounter = 0;

    /**
     * Count amount of encrypted values in this service.
     *
     * @var int
     */
    public $encryptCounter = 0;

    /** @var array */
    private $cachedDecryptions = [];

    /** @var array */
    private $cachedClassProperties = [];

    /** @var array */
    private $cachedClassPropertiesAreEmbedded = [];

    /** @var array */
    private $cachedClassPropertiesAreEncrypted = [];

    /** @var array */
    private $cachedClassesContainAnEncryptProperty = [];

    /**
     * Initialization of subscriber.
     *
     * @param Reader|AttributeReader $annReader
     * @param EncryptorInterface     $encryptor (Optional)  An EncryptorInterface
     */
    public function __construct($annReader, EncryptorInterface $encryptor)
    {
        $this->annReader        = $annReader;
        $this->encryptor        = $encryptor;
        $this->restoreEncryptor = $this->encryptor;
        $this->pac              = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Change the encryptor.
     */
    public function setEncryptor(?EncryptorInterface $encryptor = null)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * Get the current encryptor.
     *
     * @return EncryptorInterface|null returns the encryptor class or null
     */
    public function getEncryptor(): ?EncryptorInterface
    {
        return $this->encryptor;
    }

    /**
     * Restore encryptor to the one set in the constructor.
     */
    public function restoreEncryptor()
    {
        $this->encryptor = $this->restoreEncryptor;
    }

    /**
     * Listen a postLoad lifecycle event.
     * Decrypt entities property's values when loaded into the entity manger.
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad($args)
    {
        $entity = $args->getObject();
        $this->processFields($entity, $args->getObjectManager(), false);
    }

    /**
     * Listen to onflush event
     * Encrypt entities that are inserted into the database.
     */
    public function preFlush(PreFlushEventArgs $preFlushEventArgs)
    {
        $objectManager = method_exists($preFlushEventArgs, 'getObjectManager') ? $preFlushEventArgs->getObjectManager() : $preFlushEventArgs->getEntityManager();
        $unitOfWork    = $objectManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entityName => $entityArray) {
            if (isset($this->cachedDecryptions[$entityName])) {
                foreach ($entityArray as $entityId => $instance) {
                    $this->processFields($instance, $objectManager, true);
                }
            }
        }
        $this->cachedDecryptions = [];
    }

    /**
     * Listen to onflush event
     * Encrypt entities that are inserted into the database.
     */
    public function onFlush(OnFlushEventArgs $onFlushEventArgs)
    {
        $objectManager = method_exists($onFlushEventArgs, 'getObjectManager') ? $onFlushEventArgs->getObjectManager() : $onFlushEventArgs->getEntityManager();
        $unitOfWork    = $objectManager->getUnitOfWork();
        foreach ([$unitOfWork->getScheduledEntityUpdates(), $unitOfWork->getScheduledEntityInsertions()] as $scheduledEntities) {
            foreach ($scheduledEntities as $entity) {
                $encryptCounterBefore = $this->encryptCounter;
                $this->processFields($entity, $objectManager, true);
                if ($this->encryptCounter > $encryptCounterBefore) {
                    $classMetadata = $objectManager->getClassMetadata(get_class($entity));
                    $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $entity);
                }
            }
        }
    }

    /**
     * Listen to postFlush event
     * Decrypt entities after having been inserted into the database.
     */
    public function postFlush(PostFlushEventArgs $postFlushEventArgs)
    {
        $objectManager = method_exists($postFlushEventArgs, 'getObjectManager') ? $postFlushEventArgs->getObjectManager() : $postFlushEventArgs->getEntityManager();
        $unitOfWork    = $objectManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entityMap) {
            foreach ($entityMap as $entity) {
                if (method_exists($entity, '__isInitialized') && !$entity->__isInitialized()) {
                    continue;
                }
                $this->processFields($entity, $objectManager, false);
            }
        }
    }

    public function onClear(OnClearEventArgs $onClearEventArgs)
    {
        $this->cachedDecryptions = [];
        $this->decryptCounter    = 0;
        $this->encryptCounter    = 0;
    }

    /**
     * Realization of EventSubscriber interface method.
     *
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
            Events::onFlush,
            Events::preFlush,
            Events::postFlush,
            Events::onClear,
        ];
    }

    /**
     * Process (encrypt/decrypt) entities fields.
     *
     * @param object $entity             doctrine entity
     * @param bool   $isEncryptOperation If true - encrypt, false - decrypt entity
     *
     * @throws \RuntimeException|DoctrineEncryptBundleException
     */
    public function processFields(object $entity, EntityManagerInterface $entityManager, bool $isEncryptOperation = true): ?object
    {
        if (empty($this->encryptor) || !$this->containsEncryptProperties($entity)) {
            return $entity;
        }

        try {
            if (!empty($this->encryptor) && $this->containsEncryptProperties($entity)) {
                $realClass = ClassUtils::getClass($entity);

                // Get ReflectionClass of our entity
                $properties = $this->getClassProperties($realClass);

                // Foreach property in the reflection class
                foreach ($properties as $refProperty) {
                    if ($this->isPropertyAnEmbeddedMapping($refProperty)) {
                        $this->handleEmbeddedAnnotation($entity, $entityManager, $refProperty, $isEncryptOperation);
                        continue;
                    }

                    /**
                     * If property is an normal value and contains the Encrypt tag, lets encrypt/decrypt that property.
                     */
                    $encryptType = $this->getEncryptedPropertyType($refProperty);
                    if ($encryptType) {
                        $encryptDbalType = \Doctrine\DBAL\Types\Type::getType($encryptType);
                        $platform        = new MySQL80Platform();
                        $rootEntityName  = $entityManager->getClassMetadata(get_class($entity))->rootEntityName;

                        $value = $this->pac->getValue($entity, $refProperty->getName());
                        if (!is_null($value) and !empty($value)) {
                            if ($isEncryptOperation) {
                                // Convert to a string using doctrine-type
                                $usedValue = $encryptDbalType->convertToDatabaseValue($value, $platform);

                                if (isset(
                                    $this->cachedDecryptions[$rootEntityName][spl_object_id(
                                        $entity
                                    )][$refProperty->getName()][$usedValue]
                                )) {
                                    $this->pac->setValue(
                                        $entity,
                                        $refProperty->getName(),
                                        $this->cachedDecryptions[$rootEntityName][spl_object_id(
                                            $entity
                                        )][$refProperty->getName()][$usedValue]
                                    );
                                } elseif (substr(
                                    $usedValue,
                                    -strlen(self::ENCRYPTION_MARKER)
                                ) != self::ENCRYPTION_MARKER) {
                                    ++$this->encryptCounter;
                                    $currentPropValue = $this->encryptor->encrypt($usedValue).self::ENCRYPTION_MARKER;
                                    $this->pac->setValue($entity, $refProperty->getName(), $currentPropValue);
                                }
                            } else {
                                if (substr($value, -strlen(self::ENCRYPTION_MARKER)) == self::ENCRYPTION_MARKER) {
                                    ++$this->decryptCounter;
                                    $currentPropValue = $this->encryptor->decrypt(substr($value, 0, -5));
                                    $this->cachedDecryptions[$rootEntityName][spl_object_id(
                                        $entity
                                    )][$refProperty->getName()][$currentPropValue] = $value;

                                    // Convert from a string to the PHP-type again using dbal-type
                                    $actualValue = $encryptDbalType->convertToPHPValue($currentPropValue, $platform);
                                    $this->pac->setValue($entity, $refProperty->getName(), $actualValue);
                                }
                            }
                        }
                    }
                }
            }
        } catch (DoctrineEncryptBundleException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if (DoctrineEncryptExtension::$wrapExceptions) {
                throw new DoctrineEncryptBundleException('Something went wrong encrypting/decrypting a secret', 0, $e);
            }
            throw $e;
        }

        return $entity;
    }

    private function handleEmbeddedAnnotation($entity, EntityManagerInterface $entityManager, \ReflectionProperty $embeddedProperty, bool $isEncryptOperation = true)
    {
        $propName = $embeddedProperty->getName();

        $embeddedEntity = $this->pac->getValue($entity, $propName);

        if ($embeddedEntity) {
            $this->processFields($embeddedEntity, $entityManager, $isEncryptOperation);
        }
    }

    /**
     * Recursive function to get an associative array of class properties
     * including inherited ones from extended classes.
     *
     * @param string $className Class name
     *
     * @return array|\ReflectionProperty[]
     */
    private function getClassProperties(string $className): array
    {
        if (!array_key_exists($className, $this->cachedClassProperties)) {
            $reflectionClass = new \ReflectionClass($className);
            $properties      = $reflectionClass->getProperties();
            $propertiesArray = [];

            foreach ($properties as $property) {
                $propertyName                   = $property->getName();
                $propertiesArray[$propertyName] = $property;
            }

            if ($parentClass = $reflectionClass->getParentClass()) {
                $parentPropertiesArray = $this->getClassProperties($parentClass->getName());
                if (count($parentPropertiesArray) > 0) {
                    $propertiesArray = array_merge($parentPropertiesArray, $propertiesArray);
                }
            }

            $this->cachedClassProperties[$className] = $propertiesArray;
        }

        return $this->cachedClassProperties[$className];
    }

    /**
     * @return bool
     */
    private function isPropertyAnEmbeddedMapping(\ReflectionProperty $refProperty)
    {
        $key = $refProperty->getDeclaringClass()->getName().$refProperty->getName();
        if (!array_key_exists($key, $this->cachedClassPropertiesAreEmbedded)) {
            $this->cachedClassPropertiesAreEmbedded[$key] = (bool) $this->annReader->getPropertyAnnotation($refProperty, 'Doctrine\ORM\Mapping\Embedded');
        }

        return $this->cachedClassPropertiesAreEmbedded[$key];
    }

    /**
     * @return string|null
     */
    private function getEncryptedPropertyType(\ReflectionProperty $refProperty)
    {
        $key = $refProperty->getDeclaringClass()->getName().$refProperty->getName();
        if (!array_key_exists($key, $this->cachedClassPropertiesAreEncrypted)) {
            $type               = null;
            $propertyAnnotation = $this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME);
            if ($propertyAnnotation) {
                $type = $propertyAnnotation->type;
            }
            $this->cachedClassPropertiesAreEncrypted[$key] = $type;
        }

        return $this->cachedClassPropertiesAreEncrypted[$key];
    }

    private function containsEncryptProperties($entity)
    {
        $realClass = ClassUtils::getClass($entity);

        if (!array_key_exists($realClass, $this->cachedClassesContainAnEncryptProperty)) {
            $this->cachedClassesContainAnEncryptProperty[$realClass] = false;

            // Get ReflectionClass of our entity
            $properties = $this->getClassProperties($realClass);

            // Foreach property in the reflection class
            foreach ($properties as $refProperty) {
                if ($this->isPropertyAnEmbeddedMapping($refProperty)) {
                    $embeddedEntity = $this->pac->getValue($entity, $refProperty->getName());

                    if ($this->containsEncryptProperties($embeddedEntity)) {
                        $this->cachedClassesContainAnEncryptProperty[$realClass] = true;
                    }
                } else {
                    if ($this->getEncryptedPropertyType($refProperty)) {
                        $this->cachedClassesContainAnEncryptProperty[$realClass] = true;
                    }
                }
            }
        }

        return $this->cachedClassesContainAnEncryptProperty[$realClass];
    }
}
