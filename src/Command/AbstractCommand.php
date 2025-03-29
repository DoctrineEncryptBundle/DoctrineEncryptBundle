<?php

namespace Ambta\DoctrineEncryptBundle\Command;

use Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle;
use Ambta\DoctrineEncryptBundle\Service\Encrypt;
use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Console\Command\Command;

/**
 * Base command containing usefull base methods.
 **/
abstract class AbstractCommand extends Command
{
    /**
     * @var EntityManagerInterface|EntityManager
     */
    protected $entityManager;

    /**
     * @var DoctrineEncryptSubscriber
     */
    protected $subscriber;

    /**
     * @var Reader|AttributeReader
     */
    protected $annotationReader;

    /**
     * @var Encrypt
     */
    protected $service;

    /**
     * @return void
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        $annotationReader,
        DoctrineEncryptSubscriber $subscriber,
        Encrypt $service
    ) {
        parent::__construct();
        $this->entityManager    = $entityManager;
        $this->annotationReader = $annotationReader;
        $this->subscriber       = $subscriber;
        $this->service          = $service;
    }

    /**
     * Get an result iterator over the whole table of an entity.
     *
     * @return iterable|array
     */
    protected function getEntityIterator(string $entityName): iterable
    {
        $query = $this->entityManager->createQuery(sprintf('SELECT o FROM %s o', $entityName));

        return $query->toIterable();
    }

    /**
     * Get the number of rows in an entity-table.
     */
    protected function getTableCount(string $entityName): int
    {
        $query = $this->entityManager->createQuery(sprintf('SELECT COUNT(o) FROM %s o', $entityName));

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Return an array of entity-metadata for all entities
     * that have at least one encrypted property.
     * The returned array also contains counts of the total
     * amount of encrypted properties and the count of
     * encrypted properties per entity which includes 0 counts.
     */
    protected function getEncryptionableEntityDetails(): array
    {
        $encryptDetails = [
            'metaData'               => [],
            'propertyCountPerEntity' => [],
            'totalPropertyCount'     => 0
        ];

        $encryptTypes  = array_keys(AmbtaDoctrineEncryptBundle::ENCRYPT_TYPES);
        $metaDataArray = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metaDataArray as $entityMetaData) {
            if (($entityMetaData instanceof ClassMetadataInfo || $entityMetaData instanceof ClassMetadata) && $entityMetaData->isMappedSuperclass) {
                continue;
            }

            if (!array_key_exists($entityMetaData->name, $encryptDetails['propertyCountPerEntity'])) {
                $encryptDetails['propertyCountPerEntity'][$entityMetaData->name] = 0;
            }

            foreach ($entityMetaData->fieldMappings as $fieldMapping) {
                if (in_array($fieldMapping['type'], $encryptTypes)) {
                    if (!array_key_exists($entityMetaData->name, $encryptDetails['metaData'])) {
                        $encryptDetails['metaData'][$entityMetaData->name] = $entityMetaData;
                    }

                    ++$encryptDetails['propertyCountPerEntity'][$entityMetaData->name];
                    ++$encryptDetails['totalPropertyCount'];
                }
            }

            $properties      = $this->getEncryptionableProperties($entityMetaData);
            $propertiesCount = count($properties);
            if ($propertiesCount > 0) {
                if (!array_key_exists($entityMetaData->name, $encryptDetails['metaData'])) {
                    $encryptDetails['metaData'][$entityMetaData->name] = $entityMetaData;
                }

                $encryptDetails['propertyCountPerEntity'][$entityMetaData->name] += $propertiesCount;
                $encryptDetails['totalPropertyCount']                            += $propertiesCount;
            }
        }

        return $encryptDetails;
    }

    protected function getEncryptionableProperties($entityMetaData): array
    {
        // Create reflectionClass for each meta data object
        $reflectionClass = new \ReflectionClass($entityMetaData->name);
        $propertyArray   = $reflectionClass->getProperties();
        $properties      = [];

        foreach ($propertyArray as $property) {
            if ($this->annotationReader->getPropertyAnnotation($property, 'Ambta\DoctrineEncryptBundle\Configuration\Encrypted')) {
                $properties[] = $property;
            }
        }

        return $properties;
    }
}
